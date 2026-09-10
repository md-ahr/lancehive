<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class FreelancerSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', DemoData::OWNER_EMAIL)->firstOrFail();

        Freelancer::query()->updateOrCreate(
            ['slug' => DemoData::WORKSPACE_SLUG],
            [
                'name' => 'Demo Workspace',
                'status' => FreelancerStatus::Active,
                'owner_user_id' => $owner->id,
            ],
        );
    }
}
