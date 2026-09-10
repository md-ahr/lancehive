<?php

declare(strict_types=1);

use App\Features\Admin\Notifications\FreelancerInviteNotification;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

it('resends invite notification for pending freelancer owner', function () {
    Notification::fake();

    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $owner = User::factory()->freelancer()->unverified()->create();
    $freelancer = Freelancer::factory()->pending()->create(['owner_user_id' => $owner->id]);

    $this->postJson($this->apiUrl("admin/freelancers/{$freelancer->id}/resend-invite"))
        ->assertOk()
        ->assertJsonPath('message', 'Invitation resent successfully.');

    Notification::assertSentTo($owner, FreelancerInviteNotification::class);
});

it('rejects resend invite when owner already accepted invitation', function () {
    Sanctum::actingAs(User::factory()->superAdmin()->create());

    $owner = User::factory()->freelancer()->create(['email_verified_at' => now()]);
    $freelancer = Freelancer::factory()->active()->create(['owner_user_id' => $owner->id]);

    $this->postJson($this->apiUrl("admin/freelancers/{$freelancer->id}/resend-invite"))
        ->assertForbidden();
});
