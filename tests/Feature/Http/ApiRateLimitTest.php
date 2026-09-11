<?php

declare(strict_types=1);

use App\Core\Http\RateLimiting\RateLimitKey;
use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

it('returns too many requests when login rate limit is exceeded', function () {
    User::factory()->create([
        'email' => 'limited@example.com',
        'password' => 'password',
    ]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson($this->apiUrl('login'), [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    $this->postJson($this->apiUrl('login'), [
        'email' => 'limited@example.com',
        'password' => 'wrong-password',
    ])
        ->assertStatus(429)
        ->assertJsonPath('code', 'too_many_requests');
});

it('includes retry after header when login rate limit is exceeded', function () {
    User::factory()->create([
        'email' => 'retry@example.com',
        'password' => 'password',
    ]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson($this->apiUrl('login'), [
            'email' => 'retry@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->postJson($this->apiUrl('login'), [
        'email' => 'retry@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(429);

    expect($response->headers->has('Retry-After'))->toBeTrue();
});

it('returns too many requests when authenticated api rate limit is exceeded', function () {
    $workspace = $this->createTenantWorkspace();

    RateLimiter::for('api', fn ($request): Limit => Limit::perMinute(2)
        ->by(RateLimitKey::forUserOrIp($request)));

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('clients'))
        ->assertOk();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('clients'))
        ->assertOk();

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->getJson($this->apiUrl('clients'))
        ->assertStatus(429)
        ->assertJsonPath('code', 'too_many_requests');
});

it('returns too many requests when tenant write rate limit is exceeded', function () {
    $workspace = $this->createTenantWorkspace();
    $plan = Plan::factory()->create(['max_clients' => 10]);
    Subscription::factory()->for($workspace['freelancer'])->for($plan)->create([
        'trial_ends_at' => now()->addDays(7),
    ]);

    RateLimiter::for('tenant-writes', fn ($request): Limit => Limit::perMinute(2)
        ->by(RateLimitKey::forTenant($request)));

    for ($i = 1; $i <= 2; $i++) {
        $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
            ->postJson($this->apiUrl('clients'), ['name' => "Client {$i}"])
            ->assertCreated();
    }

    $this->actingAsTenant($workspace['user'], $workspace['freelancer'])
        ->postJson($this->apiUrl('clients'), ['name' => 'Client 3'])
        ->assertStatus(429)
        ->assertJsonPath('code', 'too_many_requests');
});

it('scales tenant write limits by subscription plan multiplier', function () {
    $workspace = $this->createTenantWorkspace();
    $proPlan = Plan::factory()->create(['slug' => 'pro']);
    Subscription::factory()->active()->for($workspace['freelancer'])->for($proPlan)->create();

    config([
        'rate-limiting.limits.tenant_writes' => 60,
        'rate-limiting.plan_multipliers.pro' => 2,
    ]);

    $tokenResult = $workspace['user']->createToken('api');
    $userWithToken = $workspace['user']->withAccessToken($tokenResult->accessToken);

    $request = Request::create('/api/v1/clients', 'POST', server: [
        'HTTP_X_FREELANCER_ID' => (string) $workspace['freelancer']->id,
    ]);
    $request->setUserResolver(fn () => $userWithToken);

    $limit = RateLimiter::limiter('tenant-writes')($request);

    expect($limit->maxAttempts)->toBe(120);
});
