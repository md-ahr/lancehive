<?php

declare(strict_types=1);

namespace App\Features\Settings\Models;

use Database\Factories\Settings\PlatformSettingsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'default_trial_days',
    'default_plan_slug',
    'support_email',
    'maintenance_mode',
])]
final class PlatformSettings extends Model
{
    /** @use HasFactory<PlatformSettingsFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_trial_days' => 'integer',
            'maintenance_mode' => 'boolean',
        ];
    }

    protected static function newFactory(): PlatformSettingsFactory
    {
        return PlatformSettingsFactory::new();
    }
}
