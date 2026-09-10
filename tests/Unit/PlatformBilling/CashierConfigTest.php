<?php

declare(strict_types=1);

use App\Features\Tenancy\Models\Freelancer;
use Laravel\Cashier\Cashier;

it('configures cashier for freelancer billables', function () {
    expect(config('cashier.currency'))->toBe('bdt')
        ->and(Cashier::$customerModel)->toBe(Freelancer::class);
});
