<?php

declare(strict_types=1);

use App\Features\Delivery\Models\Client;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('belongs to freelancer and has projects', function () {
    $client = Client::factory()->create();

    expect($client->freelancer)->not->toBeNull()
        ->and($client->freelancer->clients->contains($client))->toBeTrue()
        ->and($client->projects())->toBeInstanceOf(HasMany::class);
});
