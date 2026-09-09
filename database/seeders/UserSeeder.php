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
        User::factory()->freelancer()->create([
            'name' => 'Admin Freelancer',
            'email' => 'admin@lancehive.com',
        ]);

        User::factory()
            ->client()
            ->count(3)
            ->create();
    }
}
