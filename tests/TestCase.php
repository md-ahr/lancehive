<?php

namespace Tests;

use App\Core\ClientPortal\ClientContext;
use App\Core\Tenancy\TenantContext;
use App\Features\Delivery\Models\Client;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\ActsAsTenant;

abstract class TestCase extends BaseTestCase
{
    use ActsAsTenant;

    protected function setTenantContext(Freelancer|int $freelancer): void
    {
        $freelancerId = $freelancer instanceof Freelancer ? $freelancer->id : $freelancer;

        app(TenantContext::class)->setFreelancerId($freelancerId);
    }

    protected function clearTenantContext(): void
    {
        app(TenantContext::class)->clear();
    }

    protected function setClientContext(Client|int $client): void
    {
        $clientId = $client instanceof Client ? $client->id : $client;

        app(ClientContext::class)->setClientId($clientId);
    }

    protected function clearClientContext(): void
    {
        app(ClientContext::class)->clear();
    }

    protected function apiUrl(string $uri = ''): string
    {
        $prefix = '/'.config('api.prefix', 'api/v1');
        $uri = ltrim($uri, '/');

        return $uri === '' ? $prefix : $prefix.'/'.$uri;
    }
}
