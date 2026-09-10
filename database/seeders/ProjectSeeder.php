<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
        $acme = Client::query()
            ->where('freelancer_id', $freelancer->id)
            ->where('name', DemoData::CLIENT_ACME_NAME)
            ->firstOrFail();
        $globex = Client::query()
            ->where('freelancer_id', $freelancer->id)
            ->where('name', DemoData::CLIENT_GLOBEX_NAME)
            ->firstOrFail();

        Project::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id, 'client_id' => $acme->id, 'name' => 'Website Redesign'],
            [
                'hourly_rate' => 2500,
                'currency' => 'BDT',
                'status' => ProjectStatus::Active,
                'deadline' => now()->addMonths(2)->toDateString(),
            ],
        );

        Project::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id, 'client_id' => $acme->id, 'name' => 'Mobile App MVP'],
            [
                'hourly_rate' => 3000,
                'currency' => 'BDT',
                'status' => ProjectStatus::Active,
                'deadline' => now()->addMonths(4)->toDateString(),
            ],
        );

        Project::query()->updateOrCreate(
            ['freelancer_id' => $freelancer->id, 'client_id' => $globex->id, 'name' => 'API Integration'],
            [
                'hourly_rate' => 2000,
                'currency' => 'BDT',
                'status' => ProjectStatus::OnHold,
                'deadline' => null,
            ],
        );
    }
}
