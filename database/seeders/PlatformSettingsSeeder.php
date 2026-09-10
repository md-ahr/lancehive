<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Settings\Models\PlatformSettings;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        PlatformSettings::query()->updateOrCreate(
            ['id' => 1],
            [
                'default_trial_days' => 14,
                'default_plan_slug' => DemoData::PLAN_STARTER_SLUG,
                'support_email' => (string) config('mail.from.address'),
                'maintenance_mode' => false,
            ],
        );
    }
}
