<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class FreelancerMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();

        $owner = User::query()->where('email', DemoData::OWNER_EMAIL)->firstOrFail();
        $member = User::query()->where('email', DemoData::MEMBER_EMAIL)->firstOrFail();

        FreelancerMembership::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id, 'user_id' => $owner->id],
            ['role' => FreelancerMembershipRole::Owner],
        );

        FreelancerMembership::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id, 'user_id' => $member->id],
            ['role' => FreelancerMembershipRole::Member],
        );
    }
}
