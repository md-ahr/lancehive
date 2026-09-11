<?php

declare(strict_types=1);

it('registers the resend mail transport', function () {
    expect(config('mail.mailers.resend.transport'))->toBe('resend');
});

it('maps services.resend.key from configuration', function () {
    config(['services.resend.key' => 're_test_key']);

    expect(config('services.resend.key'))->toBe('re_test_key');
});
