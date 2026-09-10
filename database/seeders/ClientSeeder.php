<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();

        Client::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id, 'name' => DemoData::CLIENT_ACME_NAME],
            [
                'status' => ClientStatus::Active,
                'contact_email' => DemoData::CLIENT_PRIMARY_EMAIL,
            ],
        );

        Client::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id, 'name' => DemoData::CLIENT_GLOBEX_NAME],
            [
                'status' => ClientStatus::Active,
                'contact_email' => 'billing@globex.demo',
            ],
        );
    }
}
