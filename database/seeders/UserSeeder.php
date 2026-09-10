<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Auth\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => DemoData::SUPER_ADMIN_EMAIL,
        ]);

        User::factory()->freelancer()->create([
            'name' => 'Demo Owner',
            'email' => DemoData::OWNER_EMAIL,
        ]);

        User::factory()->freelancer()->create([
            'name' => 'Demo Member',
            'email' => DemoData::MEMBER_EMAIL,
        ]);

        User::factory()->client()->create([
            'name' => 'Acme Primary Contact',
            'email' => DemoData::CLIENT_PRIMARY_EMAIL,
        ]);

        User::factory()->client()->create([
            'name' => 'Acme Viewer',
            'email' => DemoData::CLIENT_VIEWER_EMAIL,
        ]);
    }
}
