<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Core\ClientPortal\ClientContext;
use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Laravel\Sanctum\Sanctum;

trait ActsAsTenant
{
    /**
     * @param  array<string, mixed>  $userAttributes
     * @param  array<string, mixed>  $freelancerAttributes
     * @return array{user: User, freelancer: Freelancer, membership: FreelancerMembership}
     */
    protected function createTenantWorkspace(
        array $userAttributes = [],
        array $freelancerAttributes = [],
        FreelancerMembershipRole $role = FreelancerMembershipRole::Owner,
    ): array {
        $user = User::factory()->user()->create($userAttributes);
        $freelancer = Freelancer::factory()->active()->create([
            'owner_user_id' => $user->id,
            ...$freelancerAttributes,
        ]);
        $membership = FreelancerMembership::factory()
            ->for($freelancer)
            ->for($user)
            ->state(['role' => $role])
            ->create();

        return [
            'user' => $user,
            'freelancer' => $freelancer,
            'membership' => $membership,
        ];
    }

    protected function actingAsTenant(?User $user = null, ?Freelancer $freelancer = null): static
    {
        if ($user === null || $freelancer === null) {
            $workspace = $this->createTenantWorkspace();
            $user = $workspace['user'];
            $freelancer = $workspace['freelancer'];
        }

        Sanctum::actingAs($user);

        return $this->withFreelancerContext($freelancer);
    }

    protected function withFreelancerContext(Freelancer $freelancer): static
    {
        app(TenantContext::class)->setFreelancerId($freelancer->id);

        return $this->withHeader('X-Freelancer-Id', (string) $freelancer->id);
    }

    /**
     * @param  array<string, mixed>  $userAttributes
     * @return array{user: User, client: Client, membership: ClientMembership}
     */
    protected function createClientPortalUser(
        array $userAttributes = [],
        ?Client $client = null,
        ClientMembershipRole $role = ClientMembershipRole::Viewer,
    ): array {
        $workspace = $this->createTenantWorkspace();
        $client ??= Client::factory()->for($workspace['freelancer'])->create();
        $user = User::factory()->user()->create($userAttributes);
        $membership = ClientMembership::factory()
            ->for($client)
            ->for($user)
            ->state(['role' => $role])
            ->create();

        return [
            'user' => $user,
            'client' => $client,
            'membership' => $membership,
        ];
    }

    protected function actingAsClient(?User $user = null, ?Client $client = null): static
    {
        if ($user === null || $client === null) {
            $portal = $this->createClientPortalUser();
            $user = $portal['user'];
            $client = $portal['client'];
        }

        Sanctum::actingAs($user);

        return $this->withClientContext($client);
    }

    protected function withClientContext(Client $client): static
    {
        app(ClientContext::class)->setClientId($client->id);

        return $this->withHeader('X-Client-Id', (string) $client->id);
    }
}
