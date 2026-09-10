<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\PlatformBilling\Models\Plan;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->upsert([
            [
                'name' => 'Starter',
                'slug' => DemoData::PLAN_STARTER_SLUG,
                'price_monthly' => 200,
                'price_yearly' => 2000,
                'currency' => 'BDT',
                'max_clients' => 3,
                'max_projects' => 5,
                'max_team_members' => 1,
                'is_custom' => false,
                'is_active' => true,
                'sort_order' => 1,
                'stripe_price_monthly_id' => 'price_dev_starter_monthly',
                'stripe_price_yearly_id' => 'price_dev_starter_yearly',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro',
                'slug' => DemoData::PLAN_PRO_SLUG,
                'price_monthly' => 500,
                'price_yearly' => 5000,
                'currency' => 'BDT',
                'max_clients' => 10,
                'max_projects' => 25,
                'max_team_members' => 3,
                'is_custom' => false,
                'is_active' => true,
                'sort_order' => 2,
                'stripe_price_monthly_id' => 'price_dev_pro_monthly',
                'stripe_price_yearly_id' => 'price_dev_pro_yearly',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business',
                'slug' => DemoData::PLAN_BUSINESS_SLUG,
                'price_monthly' => 1200,
                'price_yearly' => 12000,
                'currency' => 'BDT',
                'max_clients' => 30,
                'max_projects' => 100,
                'max_team_members' => 10,
                'is_custom' => false,
                'is_active' => true,
                'sort_order' => 3,
                'stripe_price_monthly_id' => 'price_dev_business_monthly',
                'stripe_price_yearly_id' => 'price_dev_business_yearly',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Custom',
                'slug' => DemoData::PLAN_CUSTOM_SLUG,
                'price_monthly' => null,
                'price_yearly' => null,
                'currency' => 'BDT',
                'max_clients' => null,
                'max_projects' => null,
                'max_team_members' => null,
                'is_custom' => true,
                'is_active' => true,
                'sort_order' => 99,
                'stripe_price_monthly_id' => null,
                'stripe_price_yearly_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], uniqueBy: ['slug']);
    }
}
