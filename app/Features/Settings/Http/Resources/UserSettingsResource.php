<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'timezone' => $this->resource['timezone'],
            'locale' => $this->resource['locale'],
            'notification_preferences' => $this->resource['notification_preferences'],
        ];
    }
}
