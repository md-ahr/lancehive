<?php

declare(strict_types=1);

use App\Features\PlatformBilling\Models\SubscriptionCharge;

it('belongs to subscription', function () {
    $charge = SubscriptionCharge::factory()->create();

    expect($charge->subscription)->not->toBeNull()
        ->and($charge->subscription->charges->contains($charge))->toBeTrue();
});
