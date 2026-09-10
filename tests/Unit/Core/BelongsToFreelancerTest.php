<?php

declare(strict_types=1);

use App\Core\Tenancy\TenantContext;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Models\Freelancer;

it('scopes queries to the active tenant context', function () {
    $freelancerA = Freelancer::factory()->active()->create();
    $freelancerB = Freelancer::factory()->active()->create();

    Client::factory()->for($freelancerA)->create();
    Client::factory()->for($freelancerB)->create();

    app(TenantContext::class)->setFreelancerId($freelancerA->id);

    expect(Client::query()->count())->toBe(1);
});

it('returns no rows when tenant context is missing', function () {
    Client::factory()->create();

    expect(Client::query()->count())->toBe(0);
});

it('auto-fills freelancer_id on create from tenant context', function () {
    $freelancer = Freelancer::factory()->active()->create();
    app(TenantContext::class)->setFreelancerId($freelancer->id);

    $client = Client::factory()->make(['freelancer_id' => null]);
    $client->freelancer_id = null;
    $client->save();

    expect($client->freelancer_id)->toBe($freelancer->id);
});
