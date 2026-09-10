<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\TenantContext;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            PlatformSettingsSeeder::class,
            UserSeeder::class,
            FreelancerSeeder::class,
            FreelancerMembershipSeeder::class,
            SubscriptionSeeder::class,
            SubscriptionChargeSeeder::class,
        ]);

        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
        app(TenantContext::class)->setFreelancerId($freelancer->id);

        $this->call([
            ClientSeeder::class,
            ProjectSeeder::class,
            TaskSeeder::class,
            TimeLogSeeder::class,
            ClientInvoiceSeeder::class,
            ClientMembershipSeeder::class,
            AdminActivityLogSeeder::class,
        ]);
    }
}
