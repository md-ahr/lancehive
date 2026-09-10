<?php

declare(strict_types=1);

use App\Features\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

it('denies unauthenticated access to admin freelancer routes', function () {
    $this->getJson($this->apiUrl('admin/freelancers'))
        ->assertUnauthorized();
});

it('denies non admin access to admin freelancer routes', function () {
    Sanctum::actingAs(User::factory()->freelancer()->create());

    $this->getJson($this->apiUrl('admin/freelancers'))
        ->assertForbidden()
        ->assertJsonPath('code', 'super_admin_required');
});
