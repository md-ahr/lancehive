<?php

declare(strict_types=1);

namespace App\Features\Settings\Cache;

use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Support\Facades\Cache;

final class WorkspaceSettingsCache
{
    private const int TTL_SECONDS = 300;

    /**
     * @return array{
     *     default_currency: string,
     *     invoice_number_prefix: string,
     *     default_tax_rate: string|null,
     *     invoice_footer_notes: string|null,
     *     business_name: string|null,
     *     business_email: string|null,
     *     business_address: string|null
     * }|null
     */
    public function get(int $freelancerId): ?array
    {
        /** @var array<string, mixed>|null $settings */
        $settings = Cache::remember(
            $this->key($freelancerId),
            self::TTL_SECONDS,
            function () use ($freelancerId): ?array {
                $freelancer = Freelancer::query()->find($freelancerId);

                if ($freelancer === null) {
                    return null;
                }

                return $this->toArray($freelancer);
            },
        );

        return $settings;
    }

    public function forget(int $freelancerId): void
    {
        Cache::forget($this->key($freelancerId));
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

    private function key(int $freelancerId): string
    {
        return "workspace_settings:freelancer:{$freelancerId}";
    }
}
