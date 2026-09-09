<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@lancehive.com',
        ]);

        User::factory()->freelancer()->create([
            'name' => 'Freelancer',
            'email' => 'freelancer@lancehive.com',
        ]);

        User::factory()
            ->client()
            ->count(3)
            ->create();
    }
}
