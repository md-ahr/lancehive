<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Services;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoiceItem;
use App\Features\ClientBilling\Models\ClientInvoicePayment;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\TimeLog;
use Illuminate\Support\Facades\DB;

final class ClientInvoiceService
{
    public function assignInvoiceNumber(ClientInvoice $invoice): void
    {
        if (filled($invoice->invoice_number) || $invoice->freelancer_id === null) {
            return;
        }

        $invoice->invoice_number = $this->generateInvoiceNumber($invoice->freelancer_id);
    }

    public function generateInvoiceNumber(int $freelancerId): string
    {
        return DB::transaction(function () use ($freelancerId): string {
            $year = now()->format('Y');
            $prefix = "INV-{$year}-";

            $lastNumber = ClientInvoice::withoutGlobalScopes()
                ->where('freelancer_id', $freelancerId)
                ->where('invoice_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('invoice_number')
                ->value('invoice_number');

            $sequence = 1;

            if ($lastNumber !== null && preg_match('/^INV-\d{4}-(\d+)$/', $lastNumber, $matches) === 1) {
                $sequence = (int) $matches[1] + 1;
            }

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * @param  array{due_date?: string|null, notes?: string|null, tax_rate?: string|null}  $attributes
     */
    public function createDraft(Project $project, array $attributes = []): ClientInvoice
    {
        $client = $project->client;

        if ($client === null) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        $invoice = ClientInvoice::query()->create([
            'project_id' => $project->id,
            'status' => ClientInvoiceStatus::Draft,
            'currency' => $project->currency,
            'subtotal' => '0.00',
            'tax_rate' => $attributes['tax_rate'] ?? null,
            'tax_amount' => '0.00',
            'total' => '0.00',
            'due_date' => $attributes['due_date'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'bill_to_name' => $client->name,
            'bill_to_email' => $client->contact_email,
            'bill_to_address' => null,
        ]);

        return $invoice->fresh();
    }

    public function prefillUnbilledTimeLogs(ClientInvoice $invoice): void
    {
        $this->assertEditableDraft($invoice);

        $project = $invoice->project;

        if ($project === null) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        $hourlyRate = (float) $project->hourly_rate;

        $timeLogs = TimeLog::query()
            ->whereHas('task', fn ($query) => $query->where('project_id', $project->id))
            ->whereNull('client_invoice_item_id')
            ->orderBy('logged_at')
            ->orderBy('id')
            ->get();

        foreach ($timeLogs as $timeLog) {
            $hours = (float) $timeLog->hours;
            $amount = $this->formatMoney($hours * $hourlyRate);

            $item = ClientInvoiceItem::query()->create([
                'client_invoice_id' => $invoice->id,
                'description' => filled($timeLog->description) ? $timeLog->description : 'Time logged',
                'quantity' => $this->formatMoneyString($hours),
                'rate' => $this->formatMoneyString($hourlyRate),
                'amount' => $this->formatMoneyString($amount),
            ]);

            $timeLog->update(['client_invoice_item_id' => $item->id]);
        }

        $this->recalculateTotals($invoice);
    }

    /**
     * @param  array{description: string, quantity: string|float, rate: string|float}  $data
     */
    public function addItem(ClientInvoice $invoice, array $data): ClientInvoiceItem
    {
        $this->assertEditableDraft($invoice);

        $quantity = (float) $data['quantity'];
        $rate = (float) $data['rate'];
        $amount = $this->formatMoney($quantity * $rate);

        return ClientInvoiceItem::query()->create([
            'client_invoice_id' => $invoice->id,
            'description' => $data['description'],
            'quantity' => $this->formatMoneyString($quantity),
            'rate' => $this->formatMoneyString($rate),
            'amount' => $this->formatMoneyString($amount),
        ]);
    }

    /**
     * @param  array{status?: string, due_date?: string|null, notes?: string|null, tax_rate?: string|null}  $data
     */
    public function updateInvoice(ClientInvoice $invoice, array $data): ClientInvoice
    {
        if ($invoice->status !== ClientInvoiceStatus::Draft) {
            throw new ApiException(ApiErrorCode::InvoiceNotEditable);
        }

        $updates = [];

        if (array_key_exists('due_date', $data)) {
            $updates['due_date'] = $data['due_date'];
        }

        if (array_key_exists('notes', $data)) {
            $updates['notes'] = $data['notes'];
        }

        if (array_key_exists('tax_rate', $data)) {
            $updates['tax_rate'] = $data['tax_rate'];
        }

        if (($data['status'] ?? null) === ClientInvoiceStatus::Sent->value) {
            $updates['status'] = ClientInvoiceStatus::Sent;
            $updates['issued_at'] = now()->toDateString();
            $updates['sent_at'] = now();
        }

        $invoice->update($updates);

        if (array_key_exists('tax_rate', $data)) {
            $this->recalculateTotals($invoice);
        }

        return $invoice->fresh();
    }

    /**
     * @param  array{
     *     amount: string|float,
     *     payment_method: string,
     *     reference?: string|null,
     *     paid_at: string,
     *     notes?: string|null
     * }  $data
     */
    public function recordPayment(ClientInvoice $invoice, array $data): ClientInvoicePayment
    {
        if (! in_array($invoice->status, [
            ClientInvoiceStatus::Sent,
            ClientInvoiceStatus::Overdue,
            ClientInvoiceStatus::Paid,
        ], true)) {
            throw new ApiException(ApiErrorCode::InvoiceNotEditable);
        }

        return ClientInvoicePayment::query()->create([
            'client_invoice_id' => $invoice->id,
            'amount' => $this->formatMoneyString((float) $data['amount']),
            'payment_method' => $data['payment_method'],
            'reference' => $data['reference'] ?? null,
            'paid_at' => $data['paid_at'],
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function assertDeletable(ClientInvoice $invoice): void
    {
        $this->assertEditableDraft($invoice);
    }

    public function outstandingBalance(ClientInvoice $invoice): string
    {
        $paidTotal = (float) $invoice->payments()->sum('amount');
        $remaining = (float) $invoice->total - $paidTotal;

        return $this->formatMoneyString(max(0, $remaining));
    }

    public function recalculateTotals(ClientInvoice $invoice): ClientInvoice
    {
        $invoice->refresh();

        $subtotal = $this->formatMoney((float) $invoice->items()->sum('amount'));
        $taxRate = $invoice->tax_rate !== null ? (float) $invoice->tax_rate : null;
        $taxAmount = $taxRate !== null && $taxRate > 0
            ? $this->formatMoney($subtotal * ($taxRate / 100))
            : 0.0;
        $total = $this->formatMoney($subtotal + $taxAmount);

        $invoice->update([
            'subtotal' => $this->formatMoneyString($subtotal),
            'tax_amount' => $this->formatMoneyString($taxAmount),
            'total' => $this->formatMoneyString($total),
        ]);

        return $this->syncPaymentStatus($invoice->fresh());
    }

    public function syncPaymentStatus(ClientInvoice $invoice): ClientInvoice
    {
        $invoice->refresh();

        $paidTotal = (float) $invoice->payments()->sum('amount');
        $total = (float) $invoice->total;

        if ($total > 0 && $paidTotal >= $total) {
            $invoice->update([
                'status' => ClientInvoiceStatus::Paid,
                'paid_at' => $invoice->paid_at ?? now(),
            ]);
        } elseif ($invoice->status === ClientInvoiceStatus::Paid && $paidTotal < $total) {
            $status = $invoice->due_date !== null && $invoice->due_date->isPast()
                ? ClientInvoiceStatus::Overdue
                : ClientInvoiceStatus::Sent;

            $invoice->update([
                'status' => $status,
                'paid_at' => null,
            ]);
        } else {
            $invoice->update(['paid_at' => null]);
        }

        return $invoice->fresh();
    }

    public function assertEditableDraft(ClientInvoice $invoice): void
    {
        if ($invoice->status !== ClientInvoiceStatus::Draft) {
            throw new ApiException(ApiErrorCode::InvoiceNotEditable);
        }
    }

    private function formatMoney(float $amount): float
    {
        return round($amount, 2);
    }

    private function formatMoneyString(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
