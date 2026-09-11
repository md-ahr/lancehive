<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('blocks api docs in production environment', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get('/docs/api')
        ->assertForbidden();

    $this->getJson('/docs/api.json')
        ->assertForbidden();
})->after(function () {
    app()->detectEnvironment(fn (): string => 'testing');
});

it('adds security headers to api responses', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('does not add hsts when request is not secure', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson($this->apiUrl('me'));

    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});
