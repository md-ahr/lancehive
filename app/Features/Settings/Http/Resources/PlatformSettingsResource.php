<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Resources;

use App\Features\Settings\Models\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PlatformSettings */
final class PlatformSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'default_trial_days' => $this->default_trial_days,
            'default_plan_slug' => $this->default_plan_slug,
            'support_email' => $this->support_email,
            'maintenance_mode' => $this->maintenance_mode,
        ];
    }
}
