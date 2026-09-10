<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Admin\Models\AdminActivityLog;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class AdminActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', DemoData::SUPER_ADMIN_EMAIL)->firstOrFail();
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();

        AdminActivityLog::query()->updateOrCreate(
            [
                'admin_user_id' => $admin->id,
                'action' => 'freelancer.onboard',
                'target_type' => Freelancer::class,
                'target_id' => $freelancer->id,
            ],
            [
                'metadata' => [
                    'workspace_slug' => DemoData::WORKSPACE_SLUG,
                    'owner_email' => DemoData::OWNER_EMAIL,
                    'plan' => DemoData::PLAN_STARTER_SLUG,
                ],
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
        );

        AdminActivityLog::query()->updateOrCreate(
            [
                'admin_user_id' => $admin->id,
                'action' => 'subscription.assign',
                'target_type' => Freelancer::class,
                'target_id' => $freelancer->id,
            ],
            [
                'metadata' => [
                    'plan' => DemoData::PLAN_STARTER_SLUG,
                    'status' => 'trialing',
                    'trial_days' => 14,
                ],
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
        );
    }
}
