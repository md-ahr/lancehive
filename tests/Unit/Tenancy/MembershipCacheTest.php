<?php

declare(strict_types=1);

use App\Features\Tenancy\Cache\MembershipCache;
use App\Features\Tenancy\Models\FreelancerMembership;
use Illuminate\Support\Facades\Cache;

it('caches memberships per user and can forget them', function () {
    Cache::flush();

    $membership = FreelancerMembership::factory()->create();
    $cache = app(MembershipCache::class);

    expect($cache->forUser($membership->user_id))->toHaveCount(1)
        ->and(Cache::has("memberships:user:{$membership->user_id}"))->toBeTrue();

    $cache->forget($membership->user_id);

    expect(Cache::has("memberships:user:{$membership->user_id}"))->toBeFalse();
});
