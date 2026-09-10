---
name: lancehive-testing
description: Use when writing or reviewing Pest tests for LanceHive. Triggers on unit test, feature test, isolation test, tenant test, or test coverage for any implementation task. Requires both unit and feature tests per task.
---

# LanceHive Testing

**Before writing tests:** activate `lancehive-guardrails` and read `docs/development/testing-strategy.md` § Plan before coding (test matrix).

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

## Tenant test helper (`ActsAsTenant`)

On `Tests\TestCase` via `Tests\Concerns\ActsAsTenant`:

```php
// User + active freelancer + membership (default: owner)
['user' => $user, 'freelancer' => $freelancer] = $this->createTenantWorkspace();

// Sanctum + X-Freelancer-Id (creates workspace when called with no args)
$this->actingAsTenant($user, $freelancer)
    ->postJson($this->apiUrl('clients'), ['name' => 'Acme'])
    ->assertCreated();

// Header only (user already authenticated)
$this->withFreelancerContext($freelancer)->getJson($this->apiUrl('clients'));
```

## Feature test template

```php
declare(strict_types=1);

it('allows owner to create client in workspace', function () {
    $this->actingAsTenant()
        ->postJson($this->apiUrl('clients'), ['name' => 'Acme'])
        ->assertCreated();
});
```

## Isolation test pattern

Two freelancers, two users. User A requests User B's resource ID → `assertNotFound()` + `code: not_found`. Same-tenant policy deny → `assertForbidden()`.

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
