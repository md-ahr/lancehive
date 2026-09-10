# LanceHive Testing

**Applies to:** `tests/**`

## Layout

Mirror `app/Features/{Feature}/` — **not** `tests/Feature/V1/`. API version is handled by `$this->apiUrl()`, not folder paths.

```
tests/Unit/{Feature}/{Entity}Test.php
tests/Feature/{Feature}/{Endpoint}Test.php
tests/Feature/{Feature}/{Entity}IsolationTest.php
```

## API URLs

Never hardcode `/api/v1`. Use `$this->apiUrl('clients')` from `Tests\TestCase`.

## Tenant feature tests

Use `Tests\Concerns\ActsAsTenant` (on `TestCase`):

```php
// Full workspace: user + active freelancer + owner membership
['user' => $user, 'freelancer' => $freelancer] = $this->createTenantWorkspace();

// Sanctum auth + X-Freelancer-Id header (creates workspace when called with no args)
$this->actingAsTenant($user, $freelancer)->postJson($this->apiUrl('clients'), [...]);

// Header only when user is already authenticated
$this->withFreelancerContext($freelancer)->getJson(...);
```

## Per implementation task

Ship **unit + feature + Scramble doc test**. Run narrow:

```bash
vendor/bin/sail artisan test --compact tests/Feature/{Feature}/{Name}Test.php
```

Full plan: `docs/development/testing-strategy.md`. Skill: `lancehive-testing`.
