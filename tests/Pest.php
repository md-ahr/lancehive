<?php

require __DIR__.'/../vendor/autoload.php';

use App\Core\ClientPortal\ClientContext;
use App\Core\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature', 'Unit');

uses(RefreshDatabase::class)->in('Feature', 'Unit');

afterEach(function (): void {
    app(TenantContext::class)->clear();
    app(ClientContext::class)->clear();
});
