<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Services;

use App\Features\ClientBilling\Models\ClientInvoice;
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

            $lastNumber = ClientInvoice::query()
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
                'paid_at' => $invoice->paid_at ?? now(),
            ]);
        } else {
            $invoice->update(['paid_at' => null]);
        }

        return $invoice->fresh();
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
