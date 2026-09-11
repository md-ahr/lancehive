<?php

/*
|--------------------------------------------------------------------------
| Drop optimized bootstrap caches before the application boots.
|--------------------------------------------------------------------------
|
| `php artisan optimize` bakes .env values (Redis rate limits, DB, etc.)
| into bootstrap/cache/*.php. PHPUnit env overrides are ignored while those
| files exist, which causes cross-test 429s and other environment drift.
|
*/
$bootstrapCacheDir = __DIR__.'/../bootstrap/cache';

foreach (['config.php', 'routes-v7.php', 'events.php'] as $cachedBootstrapFile) {
    $path = $bootstrapCacheDir.'/'.$cachedBootstrapFile;

    if (is_file($path)) {
        unlink($path);
    }
}

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
