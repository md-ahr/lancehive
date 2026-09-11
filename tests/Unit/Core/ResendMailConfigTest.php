<?php

declare(strict_types=1);

it('registers the resend mail transport and service key', function () {
    expect(config('mail.mailers.resend.transport'))->toBe('resend')
        ->and(config('services.resend.key'))->toBeNull();
});

it('resolves the resend service key from the environment', function () {
    config(['services.resend.key' => 're_test_key']);

    expect(config('services.resend.key'))->toBe('re_test_key');
});
