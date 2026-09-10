<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class ClientMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
        $acme = Client::query()
            ->where('freelancer_id', $freelancer->id)
            ->where('name', DemoData::CLIENT_ACME_NAME)
            ->firstOrFail();

        $primary = User::query()->where('email', DemoData::CLIENT_PRIMARY_EMAIL)->firstOrFail();
        $viewer = User::query()->where('email', DemoData::CLIENT_VIEWER_EMAIL)->firstOrFail();

        ClientMembership::query()->updateOrCreate(
            ['client_id' => $acme->id, 'user_id' => $primary->id],
            ['role' => ClientMembershipRole::Primary],
        );

        ClientMembership::query()->updateOrCreate(
            ['client_id' => $acme->id, 'user_id' => $viewer->id],
            ['role' => ClientMembershipRole::Viewer],
        );
    }
}
