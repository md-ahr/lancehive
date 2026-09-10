<?php

declare(strict_types=1);

namespace App\Features\Settings\Services;

use App\Features\Tenancy\Models\Freelancer;

final class WorkspaceSettingsService
{
    /**
     * @return array{
     *     default_currency: string,
     *     invoice_number_prefix: string,
     *     default_tax_rate: string|null,
     *     invoice_footer_notes: string|null,
     *     business_name: string|null,
     *     business_email: string|null,
     *     business_address: string|null
     * }
     */
    public function get(Freelancer $freelancer): array
    {
        return $this->toArray($freelancer);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Freelancer $freelancer, array $data): Freelancer
    {
        $freelancer->update($data);

        return $freelancer->fresh();
    }

    public function getDefaultCurrency(int $freelancerId): string
    {
        $currency = Freelancer::query()
            ->whereKey($freelancerId)
            ->value('default_currency');

        return is_string($currency) && $currency !== '' ? $currency : 'BDT';
    }

    /**
     * @return array{
     *     default_currency: string,
     *     invoice_number_prefix: string,
     *     default_tax_rate: string|null,
     *     invoice_footer_notes: string|null,
     *     business_name: string|null,
     *     business_email: string|null,
     *     business_address: string|null
     * }
     */
    private function toArray(Freelancer $freelancer): array
    {
        return [
            'default_currency' => $freelancer->default_currency,
            'invoice_number_prefix' => $freelancer->invoice_number_prefix,
            'default_tax_rate' => $freelancer->default_tax_rate !== null
                ? number_format((float) $freelancer->default_tax_rate, 2, '.', '')
                : null,
            'invoice_footer_notes' => $freelancer->invoice_footer_notes,
            'business_name' => $freelancer->business_name,
            'business_email' => $freelancer->business_email,
            'business_address' => $freelancer->business_address,
        ];
    }
}
