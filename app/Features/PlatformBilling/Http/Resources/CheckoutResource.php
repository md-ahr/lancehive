<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CheckoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'checkout_url' => $this->resource,
        ];
    }
}
