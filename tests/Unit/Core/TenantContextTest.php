<?php

declare(strict_types=1);

use App\Core\Tenancy\TenantContext;

it('sets and returns the active freelancer id', function () {
    $context = app(TenantContext::class);

    expect($context->freelancerId())->toBeNull()
        ->and($context->hasFreelancer())->toBeFalse();

    $context->setFreelancerId(42);

    expect($context->freelancerId())->toBe(42)
        ->and($context->hasFreelancer())->toBeTrue();
});

it('clears request-scoped tenant state', function () {
    $context = app(TenantContext::class);
    $context->setFreelancerId(7);

    $context->clear();

    expect($context->freelancerId())->toBeNull()
        ->and($context->hasFreelancer())->toBeFalse();
});
