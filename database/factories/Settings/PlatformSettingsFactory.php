<?php

declare(strict_types=1);

namespace Database\Factories\Settings;

use App\Features\Settings\Models\PlatformSettings;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformSettings>
 */
class PlatformSettingsFactory extends Factory
{
    protected $model = PlatformSettings::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'default_trial_days' => 14,
            'default_plan_slug' => DemoData::PLAN_STARTER_SLUG,
            'support_email' => config('mail.from.address', 'support@lancehive.com'),
            'maintenance_mode' => false,
        ];
    }
}
