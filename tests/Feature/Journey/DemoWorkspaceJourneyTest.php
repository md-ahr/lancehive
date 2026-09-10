<?php

declare(strict_types=1);

use App\Features\Admin\Models\AdminActivityLog;
use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Models\ClientInvoicePayment;
use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Plan;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Models\SubscriptionCharge;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Support\DemoData;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('matches seeded platform catalog to architecture plan', function () {
    expect(Plan::query()->count())->toBe(4);

    $starter = Plan::query()->where('slug', DemoData::PLAN_STARTER_SLUG)->firstOrFail();
    $custom = Plan::query()->where('slug', DemoData::PLAN_CUSTOM_SLUG)->firstOrFail();

    expect($starter->price_monthly)->toBe('200.00');
    expect($starter->max_clients)->toBe(3);
    expect($starter->max_projects)->toBe(5);
    expect($starter->max_team_members)->toBe(1);
    expect($custom->is_custom)->toBeTrue();
    expect($custom->max_clients)->toBeNull();
});

it('matches seeded tenant hierarchy to domain model', function () {
    $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
    $this->setTenantContext($freelancer);
    $owner = User::query()->where('email', DemoData::OWNER_EMAIL)->firstOrFail();

    expect($freelancer->status)->toBe(FreelancerStatus::Active);
    expect($freelancer->owner_user_id)->toBe($owner->id);
    expect($freelancer->clients()->count())->toBe(2);
    expect($freelancer->projects()->count())->toBe(3);
    expect($freelancer->memberships()->count())->toBe(2);

    $membershipRoles = $freelancer->memberships()
        ->with('user')
        ->get()
        ->mapWithKeys(fn (FreelancerMembership $membership) => [
            $membership->user->email => $membership->role,
        ])
        ->all();

    expect($membershipRoles[DemoData::OWNER_EMAIL])->toBe(FreelancerMembershipRole::Owner);
    expect($membershipRoles[DemoData::MEMBER_EMAIL])->toBe(FreelancerMembershipRole::Member);
});

it('keeps seeded delivery chain consistent', function () {
    $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
    $this->setTenantContext($freelancer);

    Project::query()->each(function (Project $project) use ($freelancer): void {
        expect($project->freelancer_id)->toBe($freelancer->id);
        expect($project->client->freelancer_id)->toBe($freelancer->id);
        expect($project->hourly_rate)->not->toBeNull();
        expect($project->currency)->toBe('BDT');
    });

    $taskCount = Task::query()
        ->whereHas('project', fn ($query) => $query->where('freelancer_id', $freelancer->id))
        ->count();

    expect($taskCount)->toBe(6);

    TimeLog::query()->each(function (TimeLog $log): void {
        expect($log->client_invoice_item_id)->toBeNull();
        expect((float) $log->hours)->toBeGreaterThan(0);
    });

    expect(TimeLog::query()->count())->toBe(5);
});

it('keeps seeded billing layers separate and valid', function () {
    $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
    $this->setTenantContext($freelancer);

    $subscription = Subscription::query()->where('freelancer_id', $freelancer->id)->firstOrFail();
    expect($subscription->status)->toBe(SubscriptionStatus::Trialing);
    expect($subscription->plan->slug)->toBe(DemoData::PLAN_STARTER_SLUG);
    expect($subscription->canWrite())->toBeTrue();
    expect($subscription->charges()->count())->toBe(2);
    expect(SubscriptionCharge::query()->where('status', 'paid')->count())->toBe(1);

    $invoices = ClientInvoice::query()->where('freelancer_id', $freelancer->id)->get();
    expect($invoices)->toHaveCount(2);
    expect($invoices->contains('status', ClientInvoiceStatus::Draft))->toBeTrue();
    expect($invoices->contains('status', ClientInvoiceStatus::Sent))->toBeTrue();

    $sentInvoice = ClientInvoice::query()
        ->where('freelancer_id', $freelancer->id)
        ->where('status', ClientInvoiceStatus::Sent)
        ->firstOrFail();

    expect($sentInvoice->invoice_number)->toBe('INV-2026-0002');
    expect($sentInvoice->items()->count())->toBe(1);
    expect($sentInvoice->total)->toBe('12500.00');

    $paidTotal = ClientInvoicePayment::query()
        ->where('client_invoice_id', $sentInvoice->id)
        ->sum('amount');

    expect(number_format((float) $paidTotal, 2, '.', ''))->toBe('5000.00');
    expect($sentInvoice->paid_at)->toBeNull();
});

it('creates seeded client portal memberships', function () {
    $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
    $this->setTenantContext($freelancer);

    $acme = Client::query()->where('name', DemoData::CLIENT_ACME_NAME)->firstOrFail();

    $memberships = ClientMembership::query()
        ->where('client_id', $acme->id)
        ->with('user')
        ->get();

    expect($memberships)->toHaveCount(2);
    expect($memberships->contains(
        fn (ClientMembership $membership) => $membership->user->email === DemoData::CLIENT_PRIMARY_EMAIL
            && $membership->role === ClientMembershipRole::Primary,
    ))->toBeTrue();
    expect($memberships->contains(
        fn (ClientMembership $membership) => $membership->user->email === DemoData::CLIENT_VIEWER_EMAIL
            && $membership->role === ClientMembershipRole::Viewer,
    ))->toBeTrue();
});

it('creates seeded admin audit trail', function () {
    $admin = User::query()->where('email', DemoData::SUPER_ADMIN_EMAIL)->firstOrFail();
    $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();

    $logs = AdminActivityLog::query()
        ->where('admin_user_id', $admin->id)
        ->where('target_type', Freelancer::class)
        ->where('target_id', $freelancer->id)
        ->get();

    expect($logs)->toHaveCount(2);
    expect($logs->pluck('action')->contains('freelancer.onboard'))->toBeTrue();
    expect($logs->pluck('action')->contains('subscription.assign'))->toBeTrue();
});

it('allows super admin to login and list users', function () {
    $token = $this->postJson($this->apiUrl('login'), [
        'email' => DemoData::SUPER_ADMIN_EMAIL,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('user.role', UserRole::SuperAdmin->value)
        ->json('token');

    $this->withToken($token)
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.email', DemoData::SUPER_ADMIN_EMAIL);

    $this->withToken($token)
        ->getJson($this->apiUrl('users'))
        ->assertOk()
        ->assertJsonCount(5, 'users');
});

it('allows workspace owner to login and access profile', function () {
    $token = $this->postJson($this->apiUrl('login'), [
        'email' => DemoData::OWNER_EMAIL,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('user.role', UserRole::Freelancer->value)
        ->json('token');

    $this->withToken($token)
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.email', DemoData::OWNER_EMAIL);

    $owner = User::query()->where('email', DemoData::OWNER_EMAIL)->firstOrFail();
    expect($owner->ownedFreelancer)->not->toBeNull();
    expect($owner->ownedFreelancer->slug)->toBe(DemoData::WORKSPACE_SLUG);
});

it('allows workspace member to login but not list all users', function () {
    $token = $this->postJson($this->apiUrl('login'), [
        'email' => DemoData::MEMBER_EMAIL,
        'password' => 'password',
    ])
        ->assertOk()
        ->json('token');

    $this->withToken($token)
        ->getJson($this->apiUrl('me'))
        ->assertOk()
        ->assertJsonPath('user.email', DemoData::MEMBER_EMAIL);

    $this->withToken($token)
        ->getJson($this->apiUrl('users'))
        ->assertForbidden();

    $member = User::query()->where('email', DemoData::MEMBER_EMAIL)->firstOrFail();
    expect($member->freelancerMemberships()->count())->toBe(1);
});

it('allows client portal users to login', function () {
    foreach ([DemoData::CLIENT_PRIMARY_EMAIL, DemoData::CLIENT_VIEWER_EMAIL] as $email) {
        $token = $this->postJson($this->apiUrl('login'), [
            'email' => $email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.role', UserRole::Client->value)
            ->json('token');

        resetAuthState();

        $this->withToken($token)
            ->getJson($this->apiUrl('me'))
            ->assertOk()
            ->assertJsonPath('user.email', $email);

        resetAuthState();
    }
});

it('invalidates token on logout', function () {
    $token = $this->postJson($this->apiUrl('login'), [
        'email' => DemoData::OWNER_EMAIL,
        'password' => 'password',
    ])->json('token');

    $this->withToken($token)
        ->postJson($this->apiUrl('logout'))
        ->assertOk();

    resetAuthState();

    $this->withToken($token)
        ->getJson($this->apiUrl('me'))
        ->assertUnauthorized();
});

function resetAuthState(): void
{
    test()->flushHeaders();
    auth()->forgetGuards();
}
