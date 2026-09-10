<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            UserSeeder::class,
            FreelancerSeeder::class,
            FreelancerMembershipSeeder::class,
            SubscriptionSeeder::class,
            SubscriptionChargeSeeder::class,
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
