# Testing Strategy

Plan tests **before** writing code. This doc defines what to test at each layer, what to skip, and how to avoid costly gaps (especially tenant isolation and billing).

Related: **`lancehive-guardrails`** (plan matrix first) · `lancehive-testing` skill · `testing-required.mdc` · [acceptance-criteria.md](../multi-tenant/acceptance-criteria.md)

---

## Test pyramid (this project)

```
        ┌─────────────┐
        │  Journey    │  Few — seeded domain invariants (DemoWorkspaceJourneyTest)
        ├─────────────┤
        │  Feature    │  Most — HTTP contract, auth, validation, isolation
        ├─────────────┤
        │  Unit       │  Targeted — enums, model behavior, service logic
        └─────────────┘
```

**No browser E2E in MVP.** This project does not install Dusk/Playwright. "Integration" here means **Laravel feature tests** (full HTTP stack + database). Do not add browser test dependencies without user approval.

---

## Mandatory per implementation task

Every task from `implementation-tasks.md` ships **all three**:

| # | Type | Covers |
|---|------|--------|
| 1 | **Unit** | Enum cases, model casts/relations/factory states, Action/Service logic without HTTP |
| 2 | **Feature** | Endpoint auth, validation, happy path, ≥1 failure mode (403/404/422) |
| 3 | **Doc** | New route path in `tests/Feature/Api/DocumentationTest.php` |

Skip none. Do not add tests beyond changed behavior.

---

## Plan before coding (agent workflow)

### Step 1 — Read the task block

From `docs/multi-tenant/implementation-tasks.md`:

- **Done when** — acceptance behavior
- **Tests** — hinted cases
- **Edge cases** — failure modes to cover

### Step 2 — Choose test files

| Artifact created | Unit test | Feature test |
|------------------|-----------|--------------|
| Enum | `tests/Unit/{Feature}/{Enum}Test.php` | — |
| Model + factory | `tests/Unit/{Feature}/{Model}Test.php` | — |
| Action / Service | `tests/Unit/{Feature}/{Action}Test.php` | Optional if fully covered by feature |
| API endpoint | — | `tests/Feature/{Feature}/{Verb}{Entity}Test.php` |
| Middleware | — | `tests/Feature/{Feature}/{Middleware}Test.php` |
| Tenant resource | — | `tests/Feature/{Feature}/{Entity}IsolationTest.php` |
| Policy only | `tests/Unit/{Feature}/{Entity}PolicyTest.php` | Covered again at feature layer for one deny case |

Mirror `app/Features/{Feature}/` — **not** `tests/Feature/V1/`.

### Step 3 — Write the test matrix (on paper or in PR description)

For each endpoint, mark which cases apply:

| Case | Assert | Required? |
|------|--------|-----------|
| Happy path | 200/201 + JSON shape | Yes |
| Unauthenticated | 401 | Yes (protected routes) |
| Non-member / wrong role | 403 | Yes (tenant routes) |
| Cross-tenant ID | 404 `not_found` | Yes (tenant resources) |
| Validation failure | 422 + `errors` | Yes (write endpoints) |
| Plan limit | 422 `plan_limit_exceeded` | When create is limited |
| Read-only subscription | 403 `workspace_read_only` | Write routes (Phase 15+) |
| Rate limit | 429 | Auth routes only |

### Step 4 — Implement code, then run narrow

```bash
vendor/bin/sail artisan test --compact tests/Unit/{Feature}/{Name}Test.php
vendor/bin/sail artisan test --compact tests/Feature/{Feature}/{Name}Test.php
```

Never run the full suite after every small edit.

---

## Layer guide

### Unit tests

**Purpose:** Fast, isolated proof of business logic and domain rules.

**Use for:**

- Enum `values()`, helpers (`isSuperAdmin()`)
- Model `casts()`, relations, scopes, factory states
- `PlanLimitService`, `ClientInvoiceService` calculations
- Policy `authorize()` matrix (direct policy invocation)

**Patterns:**

```php
declare(strict_types=1);

use App\Features\Tenancy\Services\PlanLimitService;

it('throws when client limit exceeded', function () {
    // arrange with factories — no HTTP
    expect(fn () => $service->assertCanAddClient($freelancerId))
        ->toThrow(ApiException::class);
});
```

**Do not:**

- Boot full HTTP kernel for logic testable without it
- Duplicate every feature assertion at unit layer
- Test Laravel framework behavior (e.g. "validation returns 422")

### Feature tests (integration)

**Purpose:** Prove the HTTP contract end-to-end — routing, middleware, auth, DB, JSON shape.

**Use for:**

- Every API endpoint
- Middleware behavior (`EnsureFreelancerContext`, `writable.subscription`)
- Sanctum auth flows
- Tenant isolation (Phase 11+)

**Tenant helper:** `Tests\Concerns\ActsAsTenant` on `TestCase` — `createTenantWorkspace()`, `actingAsTenant()`, `withFreelancerContext()`. See `tests/Feature/Tenancy/ActsAsTenantTest.php`.

**Template:**

```php
declare(strict_types=1);

it('allows owner to create client in workspace', function () {
    $this->actingAsTenant()
        ->postJson($this->apiUrl('clients'), ['name' => 'Acme'])
        ->assertCreated()
        ->assertJsonPath('name', 'Acme');
});
```

**Conventions:**

- `$this->apiUrl('clients')` — never hardcode `/api/v1`
- `RefreshDatabase` via `tests/Pest.php` for Feature + `Unit/Auth`
- `postJson` / `getJson` / `patchJson` / `deleteJson`
- Tenant header on every tenant request
- `dataset()` + `->with()` for role/permission matrices

### Journey tests (E2E substitute)

**Purpose:** Cross-feature invariants on seeded demo data.

**Example:** `tests/Feature/Journey/DemoWorkspaceJourneyTest.php`

**Use sparingly:** one journey test per major domain milestone — not per endpoint.

**Do not** use journey tests instead of focused feature tests.

### Scramble / OpenAPI doc tests

```php
expect($this->getJson('/docs/api.json')->json('paths'))
    ->toHaveKey('/clients');
```

Run: `vendor/bin/sail artisan test --compact tests/Feature/Api/DocumentationTest.php`

---

## Tenant isolation tests (Phase 11+)

**Required** for every tenant-scoped resource. One dedicated file per entity or group.

**Pattern:**

1. Create workspace A + user A, workspace B + user B
2. Create resource in workspace B
3. User A requests B's resource ID
4. Assert `404` (not `403`, not `200`)

```php
it('returns not found when accessing another workspaces client', function () {
    [$userA, $freelancerA] = createWorkspaceWithOwner();
    [$userB, $freelancerB] = createWorkspaceWithOwner();
    $clientB = Client::factory()->create(['freelancer_id' => $freelancerB->id]);

    Sanctum::actingAs($userA);

    $this->withHeader('X-Freelancer-Id', (string) $freelancerA->id)
        ->getJson($this->apiUrl("clients/{$clientB->id}"))
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});
```

See [acceptance-criteria.md](../multi-tenant/acceptance-criteria.md) § Phase 11 for per-resource matrix.

---

## Test data rules

| Rule | Detail |
|------|--------|
| Factories | Always use factories — never `Model::create([...])` with magic IDs in feature tests |
| Factory states | Prefer `->owner()`, `->active()`, `->freelancer()` states over manual field sets |
| Tenant binding | Every child record must belong to the same `freelancer_id` as parent |
| Two workspaces | Isolation tests always use two distinct freelancers |
| Cache | `CACHE_STORE=array` in `phpunit.xml` — never require Redis in tests |
| DB | SQLite `:memory:` in tests — do not assume PostgreSQL-specific behavior without marking test |

---

## Security test cases (minimum)

| Area | Test |
|------|------|
| Auth | Protected route without token → 401 |
| Admin | Non-admin `GET /users` → 403 |
| Membership | Valid header + non-member → 403 |
| Cross-tenant | Foreign resource ID → 404 |
| Time logs | User A cannot PATCH user B's log → 403 (same tenant) |
| Invoices | Member cannot POST invoice → 403 |
| Read-only | POST while lapsed → 403 `workspace_read_only` |
| Rate limit | 6th login in 1 min → 429 |
| Expired token | Bearer token past `SANCTUM_TOKEN_EXPIRATION` → 401 |
| Account lockout | 10 failed logins → still 422 on `errors.email`; success after lock expires |
| Production docs | `APP_ENV=production` → `/docs/api` not accessible |
| Security headers | API response includes `X-Content-Type-Options: nosniff` |

---

## What not to test

- Framework internals (Sanctum token format, Eloquent query builder)
- Third-party SDK behavior (mock Stripe at service boundary)
- Duplicate assertion at unit and feature layer for the same branch
- Snapshot of entire JSON responses (assert key paths instead)
- Full test suite on every save

---

## Tooling

```bash
# Create tests
vendor/bin/sail artisan make:test --pest StoreClientTest          # feature
vendor/bin/sail artisan make:test --pest ClientTest --unit      # unit

# Run
vendor/bin/sail artisan test --compact tests/Feature/Delivery/StoreClientTest.php
vendor/bin/sail pest tests/Unit/Delivery/ClientTest.php

# Format PHP after changes
vendor/bin/sail bin pint --dirty --format agent
```

Activate **`lancehive-guardrails`** before planning tests. Then **`testing-best-practices`** for Laravel/Pest guidance and **`lancehive-testing`** for project templates. Rule: `testing-required.mdc` · Tenancy: `tenancy-isolation.mdc`.

---

## Reference tests in repo

| File | Pattern |
|------|---------|
| `tests/Feature/Auth/LoginTest.php` | Auth + JSON shape |
| `tests/Feature/Auth/ShowAuthenticatedUserTest.php` | Role datasets |
| `tests/Feature/Api/ErrorResponseTest.php` | ApiException render + 401/403 |
| `tests/Feature/Api/DocumentationTest.php` | OpenAPI paths |
| `tests/Unit/Auth/UserRoleTest.php` | Enum datasets |
| `tests/Feature/Journey/DemoWorkspaceJourneyTest.php` | Seeded cross-feature |

---

## Agent checklist (before marking task done)

- [ ] Test matrix planned and all required cases implemented
- [ ] Unit test for domain logic (if task adds enum/model/service)
- [ ] Feature test for HTTP (if task adds/changes endpoint)
- [ ] Isolation test (if tenant-scoped resource)
- [ ] `DocumentationTest` updated (if new route)
- [ ] Narrow test run passes
- [ ] Pint run on dirty PHP files
- [ ] Task checklist `[x]` in `implementation-tasks.md`
