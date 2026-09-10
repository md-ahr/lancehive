<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WorkspaceSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'default_currency' => $this->resource['default_currency'],
            'invoice_number_prefix' => $this->resource['invoice_number_prefix'],
            'default_tax_rate' => $this->resource['default_tax_rate'],
            'invoice_footer_notes' => $this->resource['invoice_footer_notes'],
            'business_name' => $this->resource['business_name'],
            'business_email' => $this->resource['business_email'],
            'business_address' => $this->resource['business_address'],
        ];
    }
}
