# Acceptance Criteria — Deep Reference

Detailed matrices for security-critical and billing phases. **Per-task summaries** (Done when / Tests / Edge cases) live inline in [implementation-tasks.md](./implementation-tasks.md). Read the target task section first; open this file when the phase header links here.

---

## Global rules (all API tasks)

| Rule | Detail |
|------|--------|
| Base path | `/api/v1` via `$this->apiUrl()` in tests |
| Auth | Sanctum bearer token |
| Tenant header | `X-Freelancer-Id` on tenant-scoped routes |
| Cross-tenant ID | `404 not_found` — never `403` for wrong workspace |
| Responses | API Resource classes; business errors via `ApiException` + `ApiErrorCode` |
| Lists | Cursor pagination; `per_page` default 25, max 100 → `422` if exceeded |
| New routes | Feature test + path in `tests/Feature/Api/DocumentationTest.php` |
| Contract | Request/response shapes in `docs/api/endpoints/` and `docs/api/schemas/` |
| Finish | `vendor/bin/sail bin pint --dirty --format agent`; narrow test run; checklist `[x]` |
| Agent specs | `lancehive-guardrails` → `docs/development/security-and-auth.md`, `error-handling.md`, `testing-strategy.md` |

---

## Phase 2 — Tenant isolation layer

### Architecture flow

```
Request
  → auth:sanctum
  → EnsureFreelancerContext (sets TenantContext from header / membership / admin override)
  → Policy authorize (membership + role)
  → Eloquent query (BelongsToFreelancer or BelongsToTenantViaProject global scope)
  → Controller / Service
```

### TenantContext (2.1)

| Method | Contract |
|--------|----------|
| `setFreelancerId(int $id)` | Sets request-scoped tenant |
| `freelancerId(): ?int` | Returns current ID or null |
| `hasFreelancer(): bool` | True when ID is set |

**Must not** leak across HTTP requests or Pest tests — reset in `tearDown` or use fresh application instance per test.

### BelongsToFreelancer (2.2)

Applied to: `Client`, `Project`, `ClientInvoice`.

| Behavior | Expected |
|----------|----------|
| Global scope | All queries filtered by `TenantContext::freelancerId()` |
| Create | Auto-fills `freelancer_id` from context |
| No context | Queries return empty (or documented explicit bypass for admin) |
| Admin bypass | Only via `withoutGlobalScopes()` in admin/onboarding code — never in tenant controllers |

### BelongsToTenantViaProject (2.3)

Applied to: `Task`, `TimeLog`, `ClientInvoiceItem`, `ClientInvoicePayment`.

| Model | Scope chain |
|-------|-------------|
| `Task` | `project.freelancer_id` |
| `TimeLog` | `task.project.freelancer_id` |
| `ClientInvoiceItem` | `clientInvoice.freelancer_id` |
| `ClientInvoicePayment` | `clientInvoice.freelancer_id` |

Nested creates must inherit tenant through parent relation — never accept raw parent IDs without tenant validation.

### EnsureFreelancerContext (2.4)

| Scenario | HTTP | Notes |
|----------|------|-------|
| Valid `X-Freelancer-Id` + membership | Continue | Sets `TenantContext` |
| User has exactly one membership, no header | Continue | Auto-select workspace |
| User has multiple memberships, no header | `422` or `403` | Must send header |
| Non-member freelancer ID | `403 forbidden` | |
| Invalid / missing freelancer ID | `403` or `422` | Per implementation |
| Super-admin + `?freelancer_id=` on admin route | Continue | Logged in Task 3.10 |
| Suspended workspace | `403` | If enforced at middleware |

### Policy matrix (2.5 – 2.10)

| Policy | viewAny / view | create | update | delete | Member notes |
|--------|----------------|--------|--------|--------|--------------|
| Freelancer (2.5) | Member of workspace | — | Owner/admin | — | View own workspace |
| Client (2.6) | Member | Member | Member | Member (archive) | Full CRUD |
| Project (2.7) | Member | Member | Member | Member (soft) | Client must be in tenant |
| Task (2.8) | Member | Member | Member | Member (soft) | Project must be in tenant |
| TimeLog (2.9) | Member | Member | **Own logs only** | **Own logs only** | Admin/owner edits any |
| ClientInvoice (2.10) | Member | Owner/admin | Owner/admin | Owner/admin (draft) | Member read-only |

Cross-tenant resource ID at policy layer → deny → controller returns `404`.

Read-only subscription blocking is **not** in policies — handled by `EnsureWritableSubscription` (Task 15.10).

### Service boundaries (2.11)

```
Tenancy/          → onboarding, context consumers
Billing/Client/   → ClientInvoiceService (totals, numbering, bill time logs)
Billing/Platform/ → SubscriptionService (stub), PlanLimitService (stub → hardened 15.11)
```

| Rule | Verification |
|------|--------------|
| No circular imports | Static analysis or documented dependency graph |
| Invoice math | Only in `ClientInvoiceService` |
| Plan limits | Only in `PlanLimitService` — controllers call `assertCanAdd*` |
| Billing insert order | invoice → items → link `time_logs.client_invoice_item_id` |

`PlanLimitService` stub throws `422 plan_limit_exceeded` when count ≥ plan limit. `null` limit = unlimited.

### Cursor pagination (2.12)

Response must include cursor meta per [pagination schema](../api/schemas/pagination.md). `per_page=101` → `422 validation_failed`.

### Cache services (2.14)

| Cache | Key pattern | TTL | Invalidate on |
|-------|-------------|-----|---------------|
| Plans | `plans:active` | 3600s | Admin plan CRUD |
| Subscription | per freelancer | 300s | Webhook, checkout, swap, cancel, admin assign |
| Memberships | per user | 300s | Onboarding, invite, member change |

**Never cache:** plan limit counts, client/project lists, invoices, time logs.

---

## Phase 11 — Tenant isolation tests

### Test setup (required for all 11.x)

```php
// Two isolated workspaces — never share factories without explicit tenant binding
$freelancerA = Freelancer::factory()->active()->create();
$freelancerB = Freelancer::factory()->active()->create();
$userA = /* member of A only */;
$userB = /* member of B only */;
$resourceInB = /* client/project/task/invoice owned by B */;

Sanctum::actingAs($userA);
$this->withHeader('X-Freelancer-Id', (string) $freelancerA->id)
    ->getJson($this->apiUrl("clients/{$resourceInB->id}"))
    ->assertNotFound(); // not Forbidden
```

Use real HTTP + middleware — do not bypass `EnsureFreelancerContext` or global scopes in test setup.

### Task 11.1 — Client isolation

| Endpoint | Assert |
|----------|--------|
| `GET /clients` | Lists only tenant A clients |
| `GET /clients/{id}` | B's client ID → 404 |
| `PATCH /clients/{id}` | B's client ID → 404 |
| `DELETE /clients/{id}` | B's client ID → 404 |
| `POST /clients` | Creates under tenant A only |

### Task 11.2 — Project isolation

| Endpoint | Assert |
|----------|--------|
| `GET /projects` | Only tenant A |
| `GET /projects/{id}` | B's project → 404 |
| `POST /clients/{client}/projects` | B's client ID → 404 |
| `GET /clients/{client}/projects` | B's client → 404 |
| Nested routes | Wrong client/project combo → 404 |

### Task 11.3 — Task and time log isolation

| Endpoint | Assert |
|----------|--------|
| Task CRUD | B's task/project → 404 |
| `POST /tasks/{task}/time-logs` | B's task → 404 |
| `PATCH /time-logs/{id}` | User A cannot edit User B's log in same tenant → 403 |
| `PATCH /time-logs/{id}` | B's log ID → 404 for user A |
| `GET /projects/{project}/time-summary` | B's project → 404 |

### Task 11.4 — ClientInvoice isolation

| Endpoint | Assert |
|----------|--------|
| Invoice show/update/delete | B's invoice → 404 |
| `POST /client-invoices/{id}/items` | B's invoice → 404 |
| `POST /client-invoices/{id}/payments` | B's invoice → 404 |
| `GET /projects/{project}/client-invoices` | B's project → 404 |

### Task 11.5 — Super-admin override

| Actor | Action | Expected |
|-------|--------|----------|
| Super-admin | `GET /admin/freelancers` | 200 |
| Regular user | `GET /admin/freelancers` | 403 |
| Super-admin | Tenant route with `?freelancer_id=B` | 200 for B's data |
| Regular user | `?freelancer_id=` override | Ignored or 403 |
| Super-admin | Override on sensitive action | `admin_activity_logs` row created |

---

## Phase 15 — Platform subscriptions

### Subscription status → write access

| Status | GET (read) | POST/PATCH/DELETE (tenant writes) | Checkout/swap/cancel |
|--------|------------|-----------------------------------|----------------------|
| Trialing (valid) | ✓ | ✓ | ✓ |
| Trialing (expired) | ✓ | ✗ `403 workspace_read_only` | ✓ |
| Active | ✓ | ✓ | ✓ |
| PastDue | ✓ | ✓ (grace — document if different) | ✓ |
| ReadOnly | ✓ | ✗ | ✓ |
| Canceled (in period) | ✓ | ✓ until period end | ✓ |
| Canceled (post-period) | ✓ | ✗ | ✓ |
| Super-admin | ✓ | ✓ always | ✓ |

### Stripe webhook events (15.9)

| Event | Subscription update | SubscriptionCharge | Cache |
|-------|---------------------|--------------------|-------|
| `checkout.session.completed` | Active / trialing per payload | — | Forget subscription |
| `invoice.paid` | Active, period dates | Create `paid` charge | Forget subscription |
| `invoice.payment_failed` | PastDue or ReadOnly | Create `failed` charge | Forget subscription |
| `customer.subscription.updated` | Sync status, plan, dates | — | Forget subscription |
| `customer.subscription.deleted` | Canceled | — | Forget subscription |

Invalid webhook signature → `400` (do not process payload).

### Plan limits (15.11)

| Plan | max_clients | max_projects | 4th client | 6th project |
|------|-------------|--------------|------------|-------------|
| Starter | 3 | 5 | `422 plan_limit_exceeded` | `422 plan_limit_exceeded` |
| Custom (`null` limits) | unlimited | unlimited | 201 | 201 |

Race safety: `DB::transaction()` + `Subscription::lockForUpdate()` on freelancer's subscription row before count + create.

### Subscription feature test matrix (15.14)

| # | Scenario | Assert |
|---|----------|--------|
| 1 | Trialing, valid trial | `POST /clients` → 201 |
| 2 | Trial expired | `GET /clients` → 200; `POST /clients` → 403 |
| 3 | Active paid | Writes allowed |
| 4 | Starter at 3 clients | `POST /clients` → 422 |
| 5 | Custom plan, null limits | No limit 422 |
| 6 | Webhook `invoice.paid` | Status updated, charge row, cache cleared |
| 7 | Read-only | Invoice create blocked; invoice list allowed |

Mock Stripe in tests — no live API calls. Use `Mail::fake()` for 15.13.

### Admin custom plan (15.12)

| Field | Value |
|-------|-------|
| `provider` | `manual` |
| Plan | `is_custom = true`, null limits |
| Stripe | No checkout required |

Must invalidate `SubscriptionCache` and log admin action.
