---
name: lancehive-testing
description: Use when writing or reviewing Pest tests for LanceHive. Triggers on unit test, feature test, isolation test, tenant test, or test coverage for any implementation task. Requires both unit and feature tests per task.
---

# LanceHive Testing

## Test folders (not API-versioned)

Mirror `app/Features/{Feature}/` — **not** `tests/Feature/V1/`. Use `$this->apiUrl('clients')` for `/api/v1/clients`.

## Mandatory per task

| Artifact | Test type | Covers |
|----------|-----------|--------|
| Enum | Unit | cases, labels, cast round-trip |
| Model | Unit | casts, relations, factory states, helpers (`isActive()`, etc.) |
| Action/Service | Unit | business logic with faked dependencies |
| API endpoint | Feature | auth, validation, success, 403/404/422 |
| API route (new) | Doc test | path present in `/docs/api.json` |
| Tenant resource | Feature | cross-workspace isolation |

## Create tests

```bash
vendor/bin/sail artisan make:test --pest StoreClientTest          # feature
vendor/bin/sail artisan make:test --pest ClientTest --unit      # unit
```

## Feature test template

```php
declare(strict_types=1);

use App\Features\Auth\Models\User;
use App\Features\Tenancy\Models\Freelancer;
use Laravel\Sanctum\Sanctum;

it('allows owner to create client in workspace', function () {
    $user = User::factory()->freelancer()->create();
    $freelancer = Freelancer::factory()->active()->create(['owner_user_id' => $user->id]);
    // membership factory...

    Sanctum::actingAs($user);

    $this->withHeader('X-Freelancer-Id', (string) $freelancer->id)
        ->postJson($this->apiUrl('clients'), ['name' => 'Acme'])
        ->assertCreated();
});
```

## Isolation test pattern

Two freelancers, two users. User A requests User B's resource ID → `assertNotFound()` or `assertForbidden()`.

## Run narrow

```bash
vendor/bin/sail artisan test --compact tests/Unit/Delivery/ClientTest.php
vendor/bin/sail artisan test --compact tests/Feature/Delivery/StoreClientTest.php
```

## Scramble doc test

When adding routes, assert OpenAPI paths (extend `tests/Feature/Api/DocumentationTest.php`):

```php
expect($this->getJson('/docs/api.json')->json('paths'))->toHaveKey('/clients');
```

## Reference tests in repo

- `tests/Feature/Auth/LoginTest.php` — API auth patterns
- `tests/Feature/Api/DocumentationTest.php` — Scramble spec smoke tests
- `tests/Unit/Auth/UserTest.php` — model unit patterns
- `tests/Unit/Auth/UserRoleTest.php` — Pest datasets
- `tests/Feature/Journey/DemoWorkspaceJourneyTest.php` — seeded domain invariants

## Do not

- Require Redis in tests (`CACHE_STORE=array` in `phpunit.xml`).
- Test framework internals or duplicate the same assertion at unit and feature layer.
- Run full suite after every small change.
