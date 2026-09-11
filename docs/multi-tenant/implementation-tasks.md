# Implementation Tasks

Small, ordered tasks for building the multi-tenant freelancer platform. Complete each task fully (including tests where noted) before moving to the next.

**Legend**

- **Depends on:** tasks that must be done first
- **Done when:** shippable definition — used for checklist `[x]`
- **Tests:** required unit/feature/doc tests for the task
- **Edge cases:** boundaries, failures, security (task-specific; phase pattern covers the rest)
- **Est.:** rough effort (XS ≈ 30 min, S ≈ 1 hr, M ≈ 2 hr)
- **API paths:** all routes live under **`/api/v1`** (e.g. `POST /clients` → `POST /api/v1/clients`). Configure via `API_ROUTE_VERSION` in `.env`.
- **Route files:** `routes/features/v1/{feature}.php` — required from `routes/api.php` via `config('api.features_routes')`.
- **Domain files:** `app/Features/{Feature}/` — no `V1/` subfolder; models, actions, controllers are version-agnostic until v2 breaks a contract.
- **Tests:** `tests/Feature/{Feature}/` + `$this->apiUrl()` — not `tests/Feature/V1/`.
- **Checklist:** `[x]` = task meets its **Done when** criteria. After finishing a task (or the last task in a phase), update [Task checklist](#task-checklist) in this file — change `[ ]` to `[x]`. Do not mark a task done until tests pass.

See [architecture-overview.md](./architecture-overview.md) for full entity schemas and subscription design. Scale-smooth MVP rules: [design-rationale-and-scaling.md](./design-rationale-and-scaling.md).

## Acceptance criteria

Each task from Phase 2 onward has **Done when**, **Tests**, and **Edge cases** inline. Phases with many similar endpoints also define an **Acceptance pattern** once — per-task blocks list only what differs.

**Deep reference** (policy matrices, isolation setup, billing rules): [acceptance-criteria.md](./acceptance-criteria.md) — Phases **2**, **11**, and **15** only.

### Global rules (every API task)

- Routes under `/api/v1`; register in `routes/features/v1/`; assert path in `DocumentationTest`
- Sanctum auth; tenant routes require `X-Freelancer-Id`
- Cross-tenant resource ID → `404 not_found` (not `403`)
- API Resource responses; business errors via `ApiException` / `ApiErrorCode` per `docs/api/errors.md`
- List endpoints: cursor pagination; `per_page` max 100 → `422`
- Contract docs: `docs/api/endpoints/` + `docs/api/schemas/` aligned with Resources
- Before checklist `[x]`: narrow tests pass + `vendor/bin/sail bin pint --dirty --format agent`
- Agent guardrails: `lancehive-guardrails` skill → `docs/development/security-and-auth.md`, `error-handling.md`, `testing-strategy.md`

---

## Task sequencing

**Phase gates** — do not start a phase until its gate is complete:

| Phase | Gate (must be complete first) |
|-------|-------------------------------|
| 1 | 0.1 |
| 2 | 1.1 – 1.28 |
| 3 | 2.1 – 2.4 (admin routes do not need tenant middleware; onboarding needs 1.23, 1.26) |
| 4 – 8 | 2.1 – 2.12 and the previous delivery phase (4 before 5, etc.) |
| 9 | 2.4, 2.14, 1.26 |
| 10 | 1.1 – 1.28 (minimum); 8.x recommended for invoice/time-log samples |
| 11 | 4.1 – 8.9 |
| 15 | 2.14, 3.x (Stripe billing; can run in parallel with 11 after tenant APIs exist) |
| 18 | 2.4, 3.2, 8.2, 15.13 (settings APIs; integrations need invoice service + subscription notifications) |
| 19 | 7.6, 8.1, 11.1, 13.4, 15.4, 3.10 (reporting; aggregates reuse time-summary + invoice patterns; exports reuse queue/notification pattern) |

**Phase 1 order note:** Task IDs 1.16–1.17 (time logs) are numbered before 1.18–1.22 but must be **built after** 1.21 — `time_logs.client_invoice_item_id` FK targets `client_invoice_items`. Follow the section order below, not numeric ID order alone.

**Cross-phase stubs (introduced in Phase 2, completed in Phase 15):**

| Concern | Introduced | Completed |
|---------|------------|-----------|
| `PlanLimitService` — limit checks on client/project create | 2.11 (count limits + `422`; callable from 4.3/5.2) | 15.11 (`lockForUpdate`, race-safe transactions) |
| Read-only subscription writes | 2.10 (policy intent only) | 15.10 (`EnsureWritableSubscription` middleware) |

---

## Phase 0 — Prerequisites

### Task 0.1 — Verify local environment

- **Est.:** XS
- **Depends on:** —
- **Actions:**
  - Run `php artisan test`
  - Run `php artisan migrate:fresh --seed`
  - Confirm super-admin login works (`admin@lancehive.com`)
- **Done when:** All existing tests pass and seed data loads.
- **Tests:** Full suite via `vendor/bin/sail artisan test`; `migrate:fresh --seed` succeeds.
- **Edge cases:** Sail not running; seed idempotent on re-run.

---

## Phase 1 — Database foundation

Build tables and models one at a time. No API yet.

**Phase 1 — completed pattern** (tasks 1.1–1.28 are `[x]`; no per-task expansion needed):

| Artifact | Done when | Tests |
|----------|-----------|-------|
| Enum | All cases defined; cast on model/migration; API `snake_case` | Unit: cases + round-trip |
| Migration | `migrate:fresh` succeeds; FKs, indexes, uniques present | Covered by model tests |
| Model | Relations, factory (+ states), helpers per task **Actions** | Unit: relations, casts, factory |
| Invoice math (1.22) | Totals recalc from items; full payment sets `paid_at` | Unit: math + payment |
| Indexes (1.28) | ERD §8 indexes (FK + composite; no redundant left-prefix duplicates) | `EXPLAIN` uses index scan |

**Edge cases (Phase 1):** unique constraint violations; nullable columns; `null` plan limits = unlimited; one subscription per freelancer.

### Freelancer workspace (Tasks 1.1 – 1.6)

### Task 1.1 — Freelancer status enum

- **Est.:** XS
- **Depends on:** 0.1
- **Files:** `app/Enums/FreelancerStatus.php`, unit test
- **Values:** `Pending`, `Active`, `Suspended`

### Task 1.2 — Freelancers table migration

- **Est.:** XS
- **Depends on:** 1.1
- **Schema:** `id`, `name`, `slug` (unique), `status`, `owner_user_id` (FK), `timestamps`

### Task 1.3 — Freelancer model

- **Est.:** S
- **Depends on:** 1.2
- **Files:** model, factory, unit tests
- **Actions:** `owner()` belongsTo User; factory states `pending()`, `active()`, `suspended()`

### Task 1.4 — Freelancer membership role enum

- **Est.:** XS
- **Depends on:** 0.1
- **Values:** `Owner`, `Admin`, `Member`

### Task 1.5 — Freelancer memberships table migration

- **Est.:** XS
- **Depends on:** 1.2, 1.4
- **Schema:** `id`, `freelancer_id`, `user_id`, `role`, `timestamps`, unique `(freelancer_id, user_id)`

### Task 1.6 — FreelancerMembership model

- **Est.:** S
- **Depends on:** 1.4, 1.5
- **Actions:** User ↔ Freelancer many-to-many via `freelancerMemberships()` / `memberships()`

### Client (Tasks 1.7 – 1.9)

### Task 1.7 — Client status enum

- **Est.:** XS
- **Depends on:** 0.1
- **Values:** `Active`, `Archived`

### Task 1.8 — Clients table migration

- **Est.:** XS
- **Depends on:** 1.2
- **Schema:** `id`, `freelancer_id` (FK), `name`, `status`, `contact_email` (nullable), `timestamps`, index on `freelancer_id`

### Task 1.9 — Client model

- **Est.:** S
- **Depends on:** 1.7, 1.8
- **Actions:** `freelancer()` belongsTo; `projects()` hasMany; `Freelancer` hasMany `clients()`

### Project (Tasks 1.10 – 1.12)

### Task 1.10 — Project status enum

- **Est.:** XS
- **Depends on:** 0.1
- **Values:** `Active`, `OnHold`, `Completed`

### Task 1.11 — Projects table migration

- **Est.:** XS
- **Depends on:** 1.8
- **Schema:**
  - `id`, `client_id` (FK), `freelancer_id` (FK), `name`, `hourly_rate` (decimal 12,2), `currency` (default `BDT`), `status`, `deadline` (nullable), `timestamps`, `deleted_at`
  - Index on `(freelancer_id, client_id)`

### Task 1.12 — Project model

- **Est.:** S
- **Depends on:** 1.9, 1.10, 1.11
- **Actions:** SoftDeletes; `client()`, `freelancer()` belongsTo; `tasks()`, `clientInvoices()` hasMany; validate `hourly_rate` required on create

### Task (Tasks 1.13 – 1.15)

### Task 1.13 — Task status enum

- **Est.:** XS
- **Depends on:** 0.1
- **Values:** `Todo`, `InProgress`, `Done`

### Task 1.14 — Tasks table migration

- **Est.:** XS
- **Depends on:** 1.11
- **Schema:**
  - `id`, `project_id` (FK), `title`, `status`, `due_date` (nullable), `estimated_hours` (decimal 8,2 nullable), `timestamps`, `deleted_at`
  - Index on `project_id`

### Task 1.15 — Task model

- **Est.:** S
- **Depends on:** 1.13, 1.14
- **Actions:** SoftDeletes; `project()` belongsTo; `timeLogs()` hasMany; `Project` hasMany `tasks()`

### Client invoicing (Tasks 1.18 – 1.22)

Build **before** Tasks 1.16–1.17 — `time_logs.client_invoice_item_id` FK requires `client_invoice_items`.

Models: `ClientInvoice`, `ClientInvoiceItem`, `ClientInvoicePayment` (see architecture-overview.md).

### Task 1.18 — ClientInvoice status enum

- **Est.:** XS
- **Depends on:** 0.1
- **Values:** `Draft`, `Sent`, `Paid`, `Overdue`, `Void`

### Task 1.19 — Client invoices table migration

- **Est.:** S
- **Depends on:** 1.11
- **Schema:**
  - `id`, `freelancer_id` (FK), `project_id` (FK), `invoice_number`, `status`, `currency` (default `BDT`)
  - `subtotal`, `tax_rate` (nullable), `tax_amount`, `total` (decimal 12,2)
  - `issued_at`, `due_date`, `sent_at`, `paid_at` (nullable dates/datetimes)
  - `notes`, `bill_to_name`, `bill_to_email`, `bill_to_address` (nullable)
  - `timestamps`, `deleted_at`
  - Unique `(freelancer_id, invoice_number)`; index on `project_id`

### Task 1.20 — ClientInvoice model

- **Est.:** S
- **Depends on:** 1.18, 1.19
- **Actions:** SoftDeletes; BelongsToFreelancer; `project()` belongsTo; `items()`, `payments()` hasMany; auto-generate `invoice_number`

### Task 1.21 — Client invoice items table migration

- **Est.:** XS
- **Depends on:** 1.19
- **Schema:** `id`, `client_invoice_id` (FK), `description`, `quantity`, `rate`, `amount`, `timestamps`

### Task 1.22 — ClientInvoiceItem and ClientInvoicePayment models

- **Est.:** S
- **Depends on:** 1.21
- **Files:** `ClientInvoiceItem`, `client_invoice_payments` migration, `ClientInvoicePayment`
- **ClientInvoicePayment schema:** `id`, `client_invoice_id` (FK), `amount`, `payment_method` (`manual`, `bank_transfer`, `cash`, `other`), `reference` (nullable), `paid_at`, `notes` (nullable), `timestamps`
- **Done when:** Invoice subtotal/tax/total recalculates from items; marking paid updates `paid_at`

### TimeLog (Tasks 1.16 – 1.17)

Build **after** Task 1.21 — `client_invoice_item_id` FK targets `client_invoice_items`.

### Task 1.16 — Time logs table migration

- **Est.:** XS
- **Depends on:** 1.14, 1.21
- **Schema:**
  - `id`, `task_id` (FK), `user_id` (FK), `hours` (decimal 8,2), `description` (nullable), `logged_at` (datetime), `client_invoice_item_id` (FK nullable), `timestamps`
  - Index on `(task_id, user_id)`

### Task 1.17 — TimeLog model

- **Est.:** S
- **Depends on:** 1.16
- **Actions:** `task()` belongsTo; `user()` belongsTo; `Task` hasMany `timeLogs()`; `User` hasMany `timeLogs()`

### Platform subscriptions (Tasks 1.23 – 1.27)

### Task 1.23 — Plan model and migration

- **Est.:** S
- **Depends on:** 0.1
- **Schema:** `id`, `name`, `slug` (unique), `price_monthly`, `price_yearly`, `currency` (default `BDT`), `max_clients`, `max_projects`, `max_team_members` (nullable = unlimited), `is_custom`, `is_active`, `sort_order`, `timestamps`
- **Actions:** Factory; seed plans — Starter (200 BDT, 3 clients, 5 projects), Pro, Business, Custom (`is_custom = true`)

### Task 1.24 — Subscription status and interval enums

- **Est.:** XS
- **Depends on:** 0.1
- **Files:** `SubscriptionStatus` (`Trialing`, `Active`, `PastDue`, `ReadOnly`, `Canceled`), `BillingInterval` (`Monthly`, `Yearly`)

### Task 1.25 — Subscriptions table migration

- **Est.:** S
- **Depends on:** 1.2, 1.23
- **Schema:**
  - `id`, `freelancer_id` (FK, **unique** — one subscription per workspace), `plan_id` (FK), `status`, `billing_interval`
  - `trial_ends_at`, `current_period_start`, `current_period_end`, `read_only_at`, `canceled_at` (all nullable datetime)
  - `provider` (`stripe`, `manual`), `provider_subscription_id` (nullable)
  - `timestamps`
  - Index on `freelancer_id`

### Task 1.26 — Subscription model

- **Est.:** S
- **Depends on:** 1.25
- **Actions:** `freelancer()`, `plan()` belongsTo; helpers `isActive()`, `onTrial()`, `isPastDue()`, `isReadOnly()`, `canWrite()`; `Freelancer` hasOne `subscription()`

### Task 1.27 — SubscriptionCharge model and migration

- **Est.:** S
- **Depends on:** 1.26
- **Schema:** `id`, `subscription_id` (FK), `amount`, `currency` (default `BDT`), `status` (`pending`, `paid`, `failed`), `paid_at` (nullable), `provider_charge_id` (nullable UNIQUE), `timestamps`

### Task 1.28 — Performance indexes migration

- **Est.:** S
- **Depends on:** 1.6 – 1.27
- **Files:** `database/migrations/*_add_performance_indexes.php`
- **Actions:** Add all indexes from [database-erd.md §8](./database-erd.md#8-index-strategy) not already covered by FK/UK migrations
- **Include:** partial index on `time_logs (task_id) WHERE client_invoice_item_id IS NULL` (PostgreSQL)
- **Done when:** `EXPLAIN` on tenant list, `/me` membership, overdue job, and webhook lookup uses indexes

---

## Phase 2 — Tenant isolation layer

**Deep reference:** [acceptance-criteria.md § Phase 2](./acceptance-criteria.md#phase-2--tenant-isolation-layer) (policy matrix, middleware flow, cache rules).

**Acceptance pattern (2.x — infrastructure):** Unit tests for services/traits/policies; no API routes unless noted. Request-scoped `TenantContext` must not leak between tests.

### Task 2.1 — TenantContext service

- **Est.:** S
- **Depends on:** 1.3
- **Files:** `app/Services/TenantContext.php`, unit test
- **Methods:** `setFreelancerId()`, `freelancerId()`, `hasFreelancer()`
- **Done when:** All three methods work; service is request-scoped.
- **Tests:** Unit — set/get/has; clear between tests.
- **Edge cases:** `freelancerId()` null when unset; no static/global state leak.

### Task 2.2 — BelongsToFreelancer trait

- **Est.:** S
- **Depends on:** 2.1
- **Apply to:** `Client`, `Project`, `ClientInvoice`
- **Actions:** Global scope + auto-set `freelancer_id` on create
- **Done when:** Scoped queries return only tenant rows; create auto-fills `freelancer_id`.
- **Tests:** Unit — with/without context; create sets ID.
- **Edge cases:** Admin bypass only via explicit `withoutGlobalScopes()` outside tenant controllers.

### Task 2.3 — BelongsToTenantViaProject trait

- **Est.:** S
- **Depends on:** 2.1, 1.15, 1.17, 1.22
- **Apply to:** `Task`, `TimeLog`, `ClientInvoiceItem`, `ClientInvoicePayment` (scoped via project/invoice chain — not `ClientInvoice`, which uses `BelongsToFreelancer`)
- **Actions:** Scope queries via `whereHas` chain to `freelancer_id`
- **Done when:** All four models scoped via correct relation chain.
- **Tests:** Unit — cross-tenant rows invisible per model.
- **Edge cases:** Nested creates must validate parent belongs to tenant.

### Task 2.4 — EnsureFreelancerContext middleware

- **Est.:** M
- **Depends on:** 2.1, 1.6
- **Actions:** Resolve from `X-Freelancer-Id` header, membership fallback, super-admin `?freelancer_id=` override
- **Done when:** Header, single-membership fallback, and admin override set `TenantContext`; invalid cases rejected.
- **Tests:** Feature — valid header 200; non-member 403; multi-membership without header fails.
- **Edge cases:** Invalid freelancer ID; suspended workspace (if enforced here).

### Task 2.5 — FreelancerPolicy

- **Est.:** S
- **Depends on:** 1.6
- **Done when:** Members can view own workspace; non-members denied.
- **Tests:** Unit — authorize allowed/denied cases.
- **Edge cases:** Cross-tenant freelancer ID → deny.

### Task 2.6 — ClientPolicy

- **Est.:** S
- **Depends on:** 1.9
- **Done when:** All members can view clients; owner/admin can create, update, and archive (Phase 13.4 tightens member writes).
- **Tests:** Unit — member allowed; non-member denied.
- **Edge cases:** See policy matrix in acceptance-criteria.md.

### Task 2.7 — ProjectPolicy

- **Est.:** S
- **Depends on:** 1.12
- **Done when:** All members can view projects; owner/admin can create, update, and soft-delete; client must belong to tenant (Phase 13.4 tightens member writes).
- **Tests:** Unit — tenant project allowed; other tenant's client denied.
- **Edge cases:** Project on archived client (document behavior).

### Task 2.8 — TaskPolicy

- **Est.:** S
- **Depends on:** 1.15, 2.7
- **Actions:** Validate project belongs to active tenant on all actions
- **Done when:** All actions verify project tenant membership.
- **Tests:** Unit — task on foreign project denied.
- **Edge cases:** Soft-deleted project → 404 on create.

### Task 2.9 — TimeLogPolicy

- **Est.:** S
- **Depends on:** 1.17, 2.8
- **Actions:** Member can log time on own tenant's tasks; can only edit own time logs
- **Done when:** Create on tenant task allowed; member edits own log only; admin edits any.
- **Tests:** Unit — own vs other user's log; admin override.
- **Edge cases:** Billed log edit rules deferred to Phase 8.

### Task 2.10 — ClientInvoicePolicy

- **Est.:** S
- **Depends on:** 1.20, 2.7
- **Actions:** Owner/admin full CRUD; member read-only; validate project tenant on create. Read-only subscription enforcement is handled by `EnsureWritableSubscription` middleware (Task 15.10), not in this policy.
- **Done when:** Role matrix enforced; member cannot create/update/delete invoices.
- **Tests:** Unit — owner/admin write; member read-only.
- **Edge cases:** Draft-only delete enforced in Phase 8, not policy alone.

### Task 2.11 — Service layer boundaries (no circular deps)

- **Est.:** S
- **Depends on:** 2.1, 1.26
- **Files:** `app/Services/` namespace layout per [architecture-review.md §3](./architecture-review.md#3-circular-dependencies)
- **Actions:**
  - `Tenancy/` — onboarding, tenant context consumers
  - `Billing/Client/` — `ClientInvoiceService` (totals, numbering, bill time logs)
  - `Billing/Platform/` — `SubscriptionService` (stub until 15.4), `PlanLimitService` (**stub** — see below)
  - Delivery controllers call `PlanLimitService` from create actions (Tasks 4.3, 5.2) but must not import other Platform Billing services
  - Document billing insert order: invoice → items → link `time_logs.client_invoice_item_id`
  - **`PlanLimitService` stub:** `assertCanAddClient()`, `assertCanAddProject()`, `assertCanAddTeamMember()` — load subscription + plan limits; enforce counts with `422` on exceed. Task 15.11 adds `lockForUpdate()` and race-safe transactions; do not duplicate limit logic in controllers.
- **Done when:** No service imports form a cycle; invoice math lives only in `ClientInvoiceService`; `PlanLimitService` stub is callable from Phase 4–5
- **Tests:** Unit — `PlanLimitService` at limit throws `422`; invoice totals only in `ClientInvoiceService`.
- **Edge cases:** Controllers must not duplicate limit or invoice math.

### Task 2.12 — Tenant API conventions (pagination + query rules)

- **Est.:** S
- **Depends on:** 2.1
- **Files:** `app/Http/Concerns/CursorPaginates.php` (or base controller), documented in overview § API pagination
- **Actions:**
  - All list endpoints use `cursorPaginate()` — default `per_page=25`, max `100`
  - Reject `per_page > 100` with `422`
  - Controllers never call `DB::table()` for tenant models — Eloquent + scopes only
  - Time-log and invoice list endpoints **must** use cursor pagination (highest volume)
- **Done when:** Shared trait used by Phase 4–8 list routes; one feature test proves cursor meta in response
- **Tests:** Feature — cursor meta present; `per_page=101` → 422.
- **Edge cases:** Empty list returns valid meta; last page `next_cursor` null.

### Task 2.13 — Redis cache configuration

- **Est.:** XS
- **Depends on:** 0.1
- **Files:** `.env.example`, optional `config/cache.php` comment
- **Actions:**
  - Set recommended defaults in `.env.example`: `CACHE_STORE=redis`, `REDIS_HOST=127.0.0.1` (comment: use `redis` as host inside Sail)
  - Document: PHPUnit keeps `CACHE_STORE=array` (`phpunit.xml`) — tests must not require Redis
  - Verify Redis reachable when running via Sail (`compose.yaml` redis service)
  - Production: never use `CACHE_STORE=database` — PostgreSQL is for tenant data only
- **Done when:** Fresh Sail install uses Redis cache; `php artisan test` passes with array driver
- **Tests:** Full suite passes with `CACHE_STORE=array` in `phpunit.xml`.
- **Edge cases:** Tests never require live Redis.

### Task 2.14 — Cache services (plans, subscription, memberships)

- **Est.:** M
- **Depends on:** 2.13, 1.23, 1.26, 1.6
- **Files:**
  - `app/Services/Cache/PlanCache.php`
  - `app/Services/Cache/SubscriptionCache.php`
  - `app/Services/Cache/MembershipCache.php`
- **Actions:**
  - `PlanCache::activePlans()` — `Cache::remember('plans:active', 3600, fn () => Plan::where('is_active', true)->orderBy('sort_order')->get())`
  - `SubscriptionCache::forFreelancer(int $id)` — remember 5 min; return subscription + plan
  - `MembershipCache::forUser(int $userId)` — remember 5 min; return freelancer memberships for `/me`
  - Add `forget*` methods; call from admin plan CRUD, webhooks, onboarding (Tasks 3.x, 15.x, 9.x)
  - Wire `EnsureWritableSubscription` (Task 15.10) to use `SubscriptionCache` — not direct DB every request
- **Do not cache:** plan limit counts, client/project lists, invoices, time logs
- **Done when:** Feature tests mock/cache or use array driver; webhook test asserts cache invalidation
- **Tests:** Feature with `Cache::fake()` or array driver; forget methods clear keys.
- **Edge cases:** Never cache plan limit counts or tenant entity lists.

---

## Phase 3 — Super-admin freelancer onboarding

**Phase depends on:** 1.3, 1.6, 1.23, 1.24, 1.26 (subscription models for onboarding). Does not require Phase 2 tenant middleware.

**Acceptance pattern (3.x — Admin API):** Prefix `admin`; middleware `auth:sanctum` + `can:super-admin`. No `X-Freelancer-Id` required. Contract: `docs/api/endpoints/admin-freelancers.md`. Tests: 401 unauthenticated, 403 non-admin, 200/201 super-admin; path in `DocumentationTest`.

### Task 3.1 — Freelancer API resource

- **Est.:** XS
- **Depends on:** 1.3
- **Done when:** Resource matches `docs/api/schemas/freelancer.md`.
- **Tests:** Unit — expected JSON keys.
- **Edge cases:** `status` serialized as `snake_case`.

### Task 3.2 — Admin route group

- **Est.:** XS
- **Depends on:** 0.1
- **Actions:** `Route::prefix('admin')->middleware(['auth:sanctum', 'can:super-admin'])`
- **Done when:** Group registered; non-admin receives 403.
- **Tests:** Feature — unauthenticated 401; regular user 403.
- **Edge cases:** Admin routes excluded from tenant middleware.

### Task 3.3 — List freelancers (GET /admin/freelancers)

- **Est.:** S
- **Depends on:** 3.1, 3.2
- **Done when:** Paginated list returns `FreelancerResource` collection.
- **Tests:** Feature — super-admin 200; non-admin 403.
- **Edge cases:** Empty platform returns empty data array.

### Task 3.4 — Show freelancer (GET /admin/freelancers/{id})

- **Est.:** S
- **Depends on:** 3.1, 3.2, 1.26
- **Include:** owner, member count, subscription status summary
- **Done when:** Show includes owner, member count, subscription summary.
- **Tests:** Feature — 200 with nested data; unknown ID 404.
- **Edge cases:** Freelancer without subscription shows null summary.

### Task 3.5 — FreelancerOnboardingService

- **Est.:** M
- **Depends on:** 1.3, 1.6, 1.23, 1.24, 1.26
- **Actions:** Transaction — Freelancer + User + FreelancerMembership (owner) + Subscription (trialing, default plan)
- **Done when:** Single transaction creates all four records; rolls back on failure.
- **Tests:** Unit — records created; exception rolls back all.
- **Edge cases:** Custom `plan_id` and `trial_days`; duplicate owner email handling.

### Task 3.6 — Create freelancer (POST /admin/freelancers)

- **Est.:** M
- **Depends on:** 3.5, 3.1, 3.2
- **Payload:** `workspace_name`, `owner_name`, `owner_email`, optional `plan_id`, optional `trial_days`
- **Done when:** `POST` returns 201 + resource; onboarding service invoked.
- **Tests:** Feature — 201; validation 422 on missing required fields.
- **Edge cases:** Invite notification queued (3.8).

### Task 3.7 — Update freelancer status (PATCH /admin/freelancers/{id})

- **Est.:** S
- **Depends on:** 3.1, 3.2
- **Done when:** Status updates (`Active`, `Suspended`, etc.) persist.
- **Tests:** Feature — PATCH 200; invalid status 422.
- **Edge cases:** Suspend logged in admin activity log (3.10).

### Task 3.8 — Freelancer invite notification

- **Est.:** M
- **Depends on:** 3.6
- **Done when:** Notification sent on freelancer create.
- **Tests:** Feature — `Notification::fake()` assert sent to owner.
- **Edge cases:** Existing user email — link user vs create new (per implementation).

### Task 3.9 — Resend invite (POST /admin/freelancers/{id}/resend-invite)

- **Est.:** S
- **Depends on:** 3.8
- **Done when:** Resend endpoint queues/sends invite again.
- **Tests:** Feature — 200; notification resent.
- **Edge cases:** Already-active owner — 422 or no-op (document choice).

### Task 3.10 — Admin activity log (super-admin audit trail)

- **Est.:** S
- **Depends on:** 3.2
- **Schema:** `admin_activity_logs` — `id`, `admin_user_id` (FK), `action` (string), `target_type`, `target_id`, `metadata` (json, nullable), `ip_address` (nullable), `timestamps`
- **Actions:**
  - Log when super-admin uses `?freelancer_id=` override, assigns custom plan, suspends workspace
  - `AdminActivityLogger` service — call from admin controllers only
  - No UI required in MVP — queryable for support/debug
- **Done when:** Override request creates log row; test asserts log on admin freelancer show with override
- **Tests:** Feature — super-admin override creates `admin_activity_logs` row.
- **Edge cases:** Suspend and custom plan assign also logged.

---

## Phase 4 — Client management

**Phase depends on:** 2.4, 2.6, 2.11, 2.12 (complete Phase 2 first).

**Acceptance pattern (4.x — Tenant CRUD):** Middleware `auth:sanctum`, `freelancer.context`. Contract: `docs/api/endpoints/clients.md`, `docs/api/schemas/client.md`. Tests per endpoint: 201/200 success, 401, 403 non-member, 404 cross-tenant, 422 validation; path in `DocumentationTest`. Read-only subscription blocking added in Phase 15.10.

### Task 4.1 — Client API resource

- **Est.:** XS
- **Depends on:** 1.9
- **Done when:** Resource keys match `docs/api/schemas/client.md`.
- **Tests:** Unit — JSON shape.
- **Edge cases:** `status` as `snake_case` enum string.

### Task 4.2 — Tenant-scoped route group

- **Est.:** XS
- **Depends on:** 2.4
- **Middleware:** `auth:sanctum`, `freelancer.context`
- **Done when:** Route file registered; missing header fails appropriately.
- **Tests:** Feature — 403/422 without valid tenant context.
- **Edge cases:** Group separate from admin routes.

### Task 4.3 — Create client (POST /clients)

- **Est.:** S
- **Depends on:** 4.1, 4.2, 2.6, 2.11
- **Actions:** Call `PlanLimitService::assertCanAddClient()` before create (stub in 2.11; hardened in 15.11)
- **Done when:** 201 + resource; plan limit enforced.
- **Tests:** Feature — 201; at limit → 422 `plan_limit_exceeded`.
- **Edge cases:** Missing `name` → 422.

### Task 4.4 — List clients (GET /clients)

- **Est.:** S
- **Depends on:** 4.1, 4.2, 2.12
- **Actions:** Cursor pagination via Task 2.12; optional `?status=active`
- **Done when:** Cursor list; optional status filter works.
- **Tests:** Feature — pagination meta; filter returns subset.
- **Edge cases:** Empty tenant returns empty list with valid meta.

### Task 4.5 — Show client (GET /clients/{id})

- **Est.:** S
- **Depends on:** 4.1, 4.2, 2.6
- **Done when:** 200 with resource; cross-tenant 404.
- **Tests:** Feature — show own; other tenant ID 404.
- **Edge cases:** Archived client still showable.

### Task 4.6 — Update client (PATCH /clients/{id})

- **Est.:** S
- **Depends on:** 4.1, 4.2, 2.6
- **Done when:** Partial update persists; cross-tenant 404.
- **Tests:** Feature — PATCH 200; invalid status 422.
- **Edge cases:** Empty PATCH body → 200 no-op or 422 (document).

### Task 4.7 — Archive client (DELETE /clients/{id})

- **Est.:** S
- **Depends on:** 4.1, 4.2, 2.6
- **Actions:** Soft-delete or status `Archived`; projects remain
- **Done when:** Client archived; child projects unchanged.
- **Tests:** Feature — DELETE 200; projects still exist.
- **Edge cases:** Double archive idempotent.

---

## Phase 5 — Project management (client-wise)

**Phase depends on:** Phase 4, 2.7, 2.11, 2.12.

**Acceptance pattern (5.x — Project API):** Contract: `docs/api/endpoints/projects.md`, `docs/api/schemas/project.md`. Inherits tenant CRUD pattern from Phase 4. Nested routes validate client/project tenant chain.

### Task 5.1 — Project API resource

- **Est.:** XS
- **Depends on:** 1.12
- **Fields:** `client_id`, `name`, `hourly_rate`, `currency`, `status`, `deadline`
- **Done when:** Resource matches schema; decimals formatted correctly.
- **Tests:** Unit — keys and casts.
- **Edge cases:** `deadline` nullable.

### Task 5.2 — Create project (POST /clients/{client}/projects)

- **Est.:** M
- **Depends on:** 5.1, 4.2, 2.7, 2.11
- **Payload:** `name`, `hourly_rate` (required), `currency` (optional, default BDT), `deadline`, `status`
- **Actions:** Call `PlanLimitService::assertCanAddProject()` before create (stub in 2.11; hardened in 15.11)
- **Done when:** 201 under client; plan limit enforced.
- **Tests:** Feature — 201; other tenant's client 404; at limit 422.
- **Edge cases:** Missing `hourly_rate` → 422.

### Task 5.3 — List client projects (GET /clients/{client}/projects)

- **Est.:** S
- **Depends on:** 5.1, 4.2, 2.12
- **Actions:** Cursor pagination (Task 2.12)
- **Done when:** Cursor list scoped to client.
- **Tests:** Feature — pagination; wrong client 404.
- **Edge cases:** Archived client — list still works.

### Task 5.4 — List all tenant projects (GET /projects)

- **Est.:** S
- **Depends on:** 5.1, 4.2, 2.12
- **Actions:** Cursor pagination (Task 2.12)
- **Done when:** All tenant projects returned; cursor meta present.
- **Tests:** Feature — only own tenant projects.
- **Edge cases:** Soft-deleted projects excluded.

### Task 5.5 — Show project (GET /projects/{id})

- **Est.:** S
- **Depends on:** 5.1, 4.2, 2.7
- **Done when:** 200; cross-tenant 404.
- **Tests:** Feature — show; foreign ID 404.

### Task 5.6 — Update project (PATCH /projects/{id})

- **Est.:** S
- **Depends on:** 5.1, 4.2, 2.7
- **Fields:** `name`, `hourly_rate`, `status`, `deadline`
- **Done when:** Partial update works; `hourly_rate` not required on update.
- **Tests:** Feature — PATCH fields; invalid status 422.

### Task 5.7 — Soft-delete project (DELETE /projects/{id})

- **Est.:** S
- **Depends on:** 5.1, 4.2, 2.7
- **Done when:** Soft-deleted; hidden from default list; tasks remain.
- **Tests:** Feature — DELETE 200; GET list excludes deleted.

---

## Phase 6 — Task management

**Phase depends on:** Phase 5, 2.8, 2.12.

**Acceptance pattern (6.x — Task API):** Contract: `docs/api/endpoints/tasks.md`, `docs/api/schemas/task.md`. Inherits tenant CRUD pattern; nested under project.

### Task 6.1 — Task API resource

- **Est.:** XS
- **Depends on:** 1.15
- **Fields:** `title`, `status`, `due_date`, `estimated_hours`
- **Done when:** Resource matches schema.
- **Tests:** Unit — JSON keys.

### Task 6.2 — Create task (POST /projects/{project}/tasks)

- **Est.:** S
- **Depends on:** 6.1, 4.2, 2.8
- **Done when:** 201 under project; foreign project 404.
- **Tests:** Feature — 201; validation 422.

### Task 6.3 — List project tasks (GET /projects/{project}/tasks)

- **Est.:** S
- **Depends on:** 6.1, 4.2, 2.12
- **Actions:** Cursor pagination (Task 2.12)
- **Done when:** Cursor list for project.
- **Tests:** Feature — pagination meta.

### Task 6.4 — Show task (GET /tasks/{id})

- **Est.:** S
- **Depends on:** 6.1, 4.2, 2.8
- **Done when:** 200; cross-tenant 404.
- **Tests:** Feature — show; foreign ID 404.

### Task 6.5 — Update task (PATCH /tasks/{id})

- **Est.:** S
- **Depends on:** 6.1, 4.2, 2.8
- **Done when:** Status and fields update; invalid enum 422.
- **Tests:** Feature — PATCH status transition.

### Task 6.6 — Soft-delete task (DELETE /tasks/{id})

- **Est.:** S
- **Depends on:** 6.1, 4.2, 2.8
- **Done when:** Soft-deleted; time logs remain.
- **Tests:** Feature — DELETE 200; excluded from list.

---

## Phase 7 — Time logging

**Phase depends on:** Phase 6, 2.9, 2.12.

**Acceptance pattern (7.x — Time log API):** Contract: `docs/api/endpoints/time-logs.md`, `docs/api/schemas/time-log.md`. Member edits own logs only; list **must** use cursor pagination.

### Task 7.1 — TimeLog API resource

- **Est.:** XS
- **Depends on:** 1.17
- **Done when:** Resource includes `hours`, `logged_at`, `user`.
- **Tests:** Unit — JSON keys.

### Task 7.2 — Log time (POST /tasks/{task}/time-logs)

- **Est.:** S
- **Depends on:** 7.1, 4.2, 2.9
- **Payload:** `hours`, `description`, `logged_at`
- **Actions:** Auto-set `user_id` from authenticated user
- **Done when:** 201; `user_id` set from auth.
- **Tests:** Feature — 201; `hours` ≤ 0 → 422.
- **Edge cases:** Foreign task 404.

### Task 7.3 — List task time logs (GET /tasks/{task}/time-logs)

- **Est.:** S
- **Depends on:** 7.1, 4.2, 2.12
- **Actions:** **Required** cursor pagination (Task 2.12); sort by `logged_at` desc
- **Done when:** Cursor list sorted `logged_at` desc.
- **Tests:** Feature — order + pagination meta.

### Task 7.4 — Update time log (PATCH /time-logs/{id})

- **Est.:** S
- **Depends on:** 7.1, 4.2, 2.9
- **Rule:** User can only edit own logs (unless admin)
- **Done when:** Owner edits 200; other member 403; admin 200.
- **Tests:** Feature — own vs other user vs admin.
- **Edge cases:** Billed log (`client_invoice_item_id` set) → 422 (Phase 8).

### Task 7.5 — Delete time log (DELETE /time-logs/{id})

- **Est.:** S
- **Depends on:** 7.1, 4.2, 2.9
- **Done when:** Own log deleted; other member 403.
- **Tests:** Feature — delete own; foreign 404.
- **Edge cases:** Billed log not deletable.

### Task 7.6 — Project time summary (GET /projects/{project}/time-summary)

- **Est.:** S
- **Depends on:** 7.1, 4.2, 2.7
- **Returns:** Total hours logged vs `estimated_hours` sum across tasks
- **Actions:** Use SQL `SUM(hours)` aggregate — **never** load all `time_logs` into memory
- **Done when:** Returns aggregated totals via SQL `SUM`.
- **Tests:** Feature — correct totals; empty project returns 0.
- **Edge cases:** Foreign project 404; no N+1 query.

---

## Phase 8 — Client invoicing (`ClientInvoice*`)

**Phase depends on:** Phase 7, 2.10, 2.11 (`ClientInvoiceService`).

**Acceptance pattern (8.x — Invoice API):** Contract: `docs/api/endpoints/client-invoices.md`, `docs/api/schemas/client-invoice.md`. Invoice math only in `ClientInvoiceService`. Member read-only; owner/admin write.

### Task 8.1 — ClientInvoice & ClientInvoiceItem API resources

- **Est.:** S
- **Depends on:** 1.20, 1.22
- **Include:** invoice_number, subtotal, tax, total, bill_to snapshot, issued_at, due_date
- **Done when:** Resources match schema including nested items.
- **Tests:** Unit — keys; outstanding balance computed.

### Task 8.2 — Create client invoice (POST /projects/{project}/client-invoices)

- **Est.:** M
- **Depends on:** 8.1, 4.2, 2.10, 2.11
- **Actions:** Create draft; snapshot `bill_to_*` from client; auto `invoice_number`; optional pre-fill from unbilled time logs at `project.hourly_rate`
- **Done when:** Draft created with bill_to snapshot and invoice number.
- **Tests:** Feature — 201; pre-fill from unbilled logs when requested.
- **Edge cases:** No unbilled logs → empty items ok; member 403.

### Task 8.3 — Add invoice item (POST /client-invoices/{id}/items)

- **Est.:** S
- **Depends on:** 8.1, 4.2, 2.10, 2.11
- **Actions:** Recalculate subtotal, tax, total after each item change
- **Done when:** Item added; totals recalculated.
- **Tests:** Feature — math correct after add.
- **Edge cases:** Zero quantity → 422.

### Task 8.4 — List project invoices (GET /projects/{project}/client-invoices)

- **Est.:** S
- **Depends on:** 8.1, 4.2, 2.12
- **Actions:** Cursor pagination (Task 2.12)
- **Done when:** Cursor list for project invoices.
- **Tests:** Feature — pagination meta.

### Task 8.5 — Show client invoice (GET /client-invoices/{id})

- **Est.:** S
- **Depends on:** 8.1, 4.2, 2.10
- **Include:** items, payments, outstanding balance
- **Done when:** Show includes items, payments, outstanding balance.
- **Tests:** Feature — nested data; cross-tenant 404.

### Task 8.6 — Update client invoice (PATCH /client-invoices/{id})

- **Est.:** S
- **Depends on:** 8.1, 4.2, 2.10, 2.11
- **Actions:** `draft` → `sent` sets `issued_at`, `sent_at`; update `due_date`, `notes`, tax_rate
- **Done when:** Status transition sets timestamps; tax recalculates.
- **Tests:** Feature — draft→sent sets dates.
- **Edge cases:** Member 403; sent invoice field restrictions (document).

### Task 8.7 — Record client payment (POST /client-invoices/{id}/payments)

- **Est.:** S
- **Depends on:** 8.1, 4.2, 2.10, 2.11
- **Payload:** `amount`, `payment_method` (`manual`, `bank_transfer`, `cash`, `other`), `reference`, `paid_at`, `notes`
- **Actions:** Manual record only (MVP); auto-mark `paid` when payments ≥ total
- **Done when:** Payment recorded; auto `paid` when sum ≥ total.
- **Tests:** Feature — partial payment; full payment marks paid.
- **Edge cases:** Overpayment handling (document).

### Task 8.8 — Soft-delete client invoice (DELETE /client-invoices/{id})

- **Est.:** S
- **Depends on:** 8.1, 4.2, 2.10
- **Rule:** Only `draft` invoices deletable
- **Done when:** Draft deleted; sent invoice → 422.
- **Tests:** Feature — draft 200; sent 422.

### Task 8.9 — Mark overdue job

- **Est.:** S
- **Depends on:** 1.20
- **Files:** `app/Features/ClientBilling/Jobs/MarkOverdueClientInvoicesJob.php` (scheduled daily in `bootstrap/app.php`)
- **Actions:** Daily — `sent` past `due_date` → `overdue`
- **Done when:** Job marks eligible invoices overdue.
- **Tests:** Unit/job — sent + past due → overdue; paid skipped.
- **Edge cases:** Already overdue idempotent.

---

## Phase 9 — Auth and /me enhancements ✅

**Phase depends on:** 2.4, 2.14, 1.6, 1.26.

**Acceptance pattern (9.x — /me):** Contract: `docs/api/endpoints/me.md`. Uses `MembershipCache` and `SubscriptionCache`. Auth only — no `X-Freelancer-Id` on `/me` itself.

### Task 9.1 — FreelancerMembershipResource

- **Est.:** XS
- **Depends on:** 1.6
- **Done when:** Resource includes role and freelancer summary.
- **Tests:** Unit — JSON keys.

### Task 9.2 — Enhance GET /me

- **Est.:** M
- **Depends on:** 9.1, 2.14, 1.26
- **Include:** user, freelancer memberships, active freelancer, subscription status summary
- **Done when:** Response includes user, memberships, active workspace, subscription summary.
- **Tests:** Feature — 200 shape; uses cache services.
- **Edge cases:** User with no memberships → empty array.

### Task 9.3 — Workspace switch via header

- **Est.:** S
- **Depends on:** 2.4, 9.2
- **Document:** `X-Freelancer-Id` header
- **Done when:** Documented in `docs/api/conventions.md` and me endpoint doc.
- **Tests:** Feature — subsequent tenant request respects switched header.
- **Edge cases:** Invalid membership for header → 403.

---

## Phase 10 — Seed data and dev ergonomics

**Phase depends on:** 1.1 – 1.28 (minimum). Run after Phase 8 for realistic invoice/time-log samples.

### Task 10.1 — Update seeders

- **Est.:** M
- **Depends on:** 1.1 – 1.28
- **Create:** super-admin, one freelancer workspace, 2 clients, 3 projects (with hourly_rate), tasks, time logs, sample ClientInvoice, BDT plans (Starter 200/3/5), trialing subscription
- **Done when:** `migrate:fresh --seed` creates full demo dataset per spec.
- **Tests:** Optional journey test or manual verify; seed idempotent.
- **Edge cases:** Re-run seed without duplicate key violations.

### Task 10.2 — Dev credentials docs

- **Est.:** XS
- **Depends on:** 10.1
- **Files:** `docs/multi-tenant/README.md`
- **Done when:** README lists login emails/passwords and sample workspace IDs.
- **Tests:** Manual review.
- **Edge cases:** Dev-only credentials clearly labeled.

---

## Phase 11 — Tenant isolation tests (critical)

**Phase depends on:** 4.1 – 8.9 (tenant APIs must exist). Do not skip.

**Deep reference:** [acceptance-criteria.md § Phase 11](./acceptance-criteria.md#phase-11--tenant-isolation-tests) (setup pattern, endpoint matrices).

**Acceptance pattern (11.x):** Two freelancers, two users. Real HTTP + middleware — no scope bypass. Cross-tenant ID → `assertNotFound()`.

### Task 11.1 — Client isolation tests

- **Est.:** S
- **Depends on:** 4.1 – 4.7
- **Done when:** All client endpoints tested for cross-tenant 404 per matrix.
- **Tests:** Feature — list/show/update/delete/create isolation.
- **Edge cases:** List never leaks other tenant names/IDs.

### Task 11.2 — Project isolation tests

- **Est.:** S
- **Depends on:** 5.1 – 5.7
- **Done when:** Project + nested client routes isolated.
- **Tests:** Feature — nested and flat routes per matrix.
- **Edge cases:** Wrong client/project combo → 404.

### Task 11.3 — Task and time log isolation tests

- **Est.:** S
- **Depends on:** 6.1 – 6.6, 7.1 – 7.6
- **Done when:** Task CRUD + time log ownership rules tested.
- **Tests:** Feature — cross-tenant 404; same-tenant other user log → 403.
- **Edge cases:** Time summary on foreign project → 404.

### Task 11.4 — ClientInvoice isolation tests

- **Est.:** S
- **Depends on:** 8.1 – 8.9
- **Done when:** Invoice, items, payments routes isolated.
- **Tests:** Feature — per matrix in acceptance-criteria.md.
- **Edge cases:** Payment on foreign invoice → 404.

### Task 11.5 — Super-admin tenant override tests

- **Est.:** S
- **Depends on:** 2.4, 3.1 – 3.10
- **Done when:** Admin override works; regular user cannot; activity logged.
- **Tests:** Feature — admin 200 with override; user denied; log row exists.
- **Edge cases:** Override without admin role ignored.

---

## Phase 12 — Role model cleanup (optional)

### Task 12.1 — Add UserRole::User

### Task 12.2 — Map seed users to memberships

### Task 12.3 — Deprecate global role helpers

---

## Phase 13 — Freelancer team (post-MVP)

### Task 13.1 — ClientMembership table stub

### Task 13.2 — Invite freelancer member

### Task 13.3 — List/remove workspace members

### Task 13.4 — Role-based permission tightening

---

## Phase 14 — Client portal (post-MVP)

### Task 14.1 — ClientMembership model

### Task 14.2 — EnsureClientContext middleware

### Task 14.3 — Invite client member

### Task 14.4 — Portal read API (clients, projects, invoices)

### Task 14.5 — Enhance /me with client memberships

---

## Phase 15 — Platform subscriptions (freelancer pays you)

Charge freelancers monthly or yearly. See [architecture-overview.md — Subscription model](./architecture-overview.md#subscription-model-platform-billing).

**Phase depends on:** 2.14, 3.x, 4.1 – 8.9 (tenant APIs for integration tests). `PlanLimitService` stub from 2.11 must exist before 15.11.

**Deep reference:** [acceptance-criteria.md § Phase 15](./acceptance-criteria.md#phase-15--platform-subscriptions) (status→write matrix, webhook events, plan limits).

**Acceptance pattern (15.x — Billing):** Contract: `docs/api/endpoints/subscription.md`, `docs/api/endpoints/webhooks.md`. Mock Stripe in tests — no live API. Invalidate `SubscriptionCache` on every subscription mutation. Subscription self-serve routes always writable even when tenant is read-only.

### Task 15.1 — Install Laravel Cashier (Stripe)

- **Est.:** M
- **Depends on:** 0.1
- **Actions:** `composer require laravel/cashier`; publish migrations; add Stripe keys to `.env`
- **Done when:** Cashier configured; Stripe test mode works
- **Tests:** Config/migration smoke; Cashier migrations run.
- **Edge cases:** Keys in `.env.example` placeholders only.

### Task 15.2 — Sync Plan model with Stripe products

- **Est.:** M
- **Depends on:** 15.1, 1.23
- **Actions:** Add `stripe_price_monthly_id`, `stripe_price_yearly_id` to `plans`; artisan command or seeder to create Stripe products/prices
- **Done when:** Each plan has Stripe price IDs for both intervals
- **Tests:** Unit or command test — seeded plans have price IDs.
- **Edge cases:** Missing price ID → checkout fails gracefully.

### Task 15.3 — Super-admin plan CRUD (GET/POST/PATCH /admin/plans)

- **Est.:** M
- **Depends on:** 3.2, 15.2
- **Who:** Super-admin only
- **Done when:** Admin can list/create/update plans; invalidates `PlanCache`.
- **Tests:** Feature — super-admin 200; non-admin 403; path in `DocumentationTest`.
- **Edge cases:** Deactivating plan excludes from `activePlans()`.

### Task 15.4 — SubscriptionService

- **Est.:** M
- **Depends on:** 15.1, 1.26, 2.14
- **Methods:**
  - `startTrial(Freelancer, Plan, int $days)`
  - `createCheckoutSession(Freelancer, Plan, BillingInterval)`
  - `swapPlan(Freelancer, Plan, BillingInterval)`
  - `cancel(Freelancer)`
  - `syncFromStripeWebhook(payload)`
- **Done when:** All methods implemented; cache forget on mutations.
- **Tests:** Unit — each method with Stripe fake/mock.
- **Edge cases:** Webhook idempotency for duplicate events.

### Task 15.5 — GET /subscription (current plan & status)

- **Est.:** S
- **Depends on:** 15.4, 4.2
- **Who:** Freelancer owner
- **Returns:** plan name, status, interval, trial/period dates, days remaining
- **Done when:** Returns plan, status, interval, dates, days remaining.
- **Tests:** Feature — 200 shape; uses `SubscriptionCache`.
- **Edge cases:** No subscription → 404 or null (document).

### Task 15.6 — POST /subscription/checkout

- **Est.:** M
- **Depends on:** 15.4, 4.2
- **Payload:** `plan_id`, `billing_interval` (`monthly` | `yearly`)
- **Returns:** Stripe Checkout URL
- **Done when:** Returns checkout URL; invalid plan 422.
- **Tests:** Feature — mock Stripe session URL returned.
- **Edge cases:** Works when workspace is read-only.

### Task 15.7 — POST /subscription/swap

- **Est.:** M
- **Depends on:** 15.4, 4.2
- **Actions:** Change plan or switch monthly ↔ yearly (Stripe proration)
- **Done when:** Plan/interval swap updates subscription via Stripe.
- **Tests:** Feature — mock Stripe swap.
- **Edge cases:** Works when read-only.

### Task 15.8 — POST /subscription/cancel

- **Est.:** S
- **Depends on:** 15.4, 4.2
- **Actions:** Cancel at period end; set `canceled_at`
- **Done when:** `canceled_at` set; cancel at period end in Stripe.
- **Tests:** Feature — 200; subscription status updated.
- **Edge cases:** Works when read-only.

### Task 15.9 — Stripe webhook handler (POST /webhooks/stripe)

- **Est.:** M
- **Depends on:** 15.4, 2.14
- **Events:** `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`
- **Actions:** Update `Subscription` status; create `SubscriptionCharge` records; transition to `read_only` on failure
- **Done when:** All five events handled; charges created; cache invalidated.
- **Tests:** Feature — mock each event payload per matrix in acceptance-criteria.md.
- **Edge cases:** Invalid signature → 400; duplicate `provider_charge_id` ignored.

### Task 15.10 — EnsureWritableSubscription middleware

- **Est.:** M
- **Depends on:** 15.4, 2.14, 4.2
- **Actions:**
  - **Always allow** GET/read routes
  - **Block writes** when status is `read_only`, `canceled` (post-period), or trial expired
  - **Always allow** subscription checkout/swap/cancel routes and super-admin
- **Apply to:** Tenant write route group (after `freelancer.context`)
- **Done when:** Read-only blocks tenant writes; reads and subscription routes exempt.
- **Tests:** Feature — expired trial GET 200, POST 403 `workspace_read_only`.
- **Edge cases:** Super-admin always writes; uses `SubscriptionCache`.

### Task 15.11 — Plan limit hardening (PlanLimitService)

- **Est.:** M
- **Depends on:** 2.11, 1.26, 4.3, 5.2
- **Actions:**
  - Upgrade the Task 2.11 stub — do not duplicate limit logic in controllers
  - Wrap check + create in `DB::transaction()` with `Subscription::lockForUpdate()` on freelancer's subscription row — prevents race at limit boundary
  - `null` plan limits = unlimited (custom plans)
  - Example: Starter (200 BDT) — 3 clients, 5 projects → 422 with upgrade message on exceed
- **Done when:** Race-safe limits; 4th client on Starter → 422; custom unlimited works.
- **Tests:** Feature — at-limit 422; custom plan no limit.
- **Edge cases:** `lockForUpdate` inside transaction before count + create.

### Task 15.12 — Admin assign custom plan (PATCH /admin/freelancers/{id}/subscription)

- **Est.:** M
- **Depends on:** 15.4, 3.2, 2.14
- **Actions:** Super-admin assigns `is_custom` plan; set `provider = manual`; unlimited limits; skip Stripe checkout
- **Done when:** Admin assigns custom plan; `provider = manual`; cache cleared.
- **Tests:** Feature — super-admin 200; activity logged.
- **Edge cases:** No Stripe checkout required.

### Task 15.13 — Subscription notification emails

- **Est.:** S
- **Depends on:** 15.4
- **Triggers:** Trial ending (3 days), payment failed, subscription canceled, renewal receipt
- **Done when:** All four triggers send mail.
- **Tests:** Feature — `Mail::fake()` per trigger.
- **Edge cases:** Trial ending only fires once per trial.

### Task 15.14 — Subscription feature tests

- **Est.:** M
- **Depends on:** 15.5 – 15.12, 4.3, 5.2
- **Cases:**
  - Trial freelancer can write
  - Expired trial → read-only (GET ok, POST 403)
  - Active subscription allows writes
  - Plan limit blocks 4th client on Starter
  - Custom plan with null limits allows unlimited
  - Webhook updates status and creates SubscriptionCharge (mock Stripe)
- **Done when:** All cases in **Cases** list pass in dedicated test file.
- **Tests:** Feature — full matrix in acceptance-criteria.md § 15.14.
- **Edge cases:** No live Stripe; no Redis required.

---

## Phase 18 — Settings (user, workspace, platform)

Configurable preferences at three scopes with role-based access. See [architecture-overview.md — Settings model](./architecture-overview.md#settings-model-phase-18).

**Phase depends on:** 2.4 (tenant middleware), 3.2 (admin routes), 8.2 (invoice numbering integration), 15.13 (notification preference gates).

**Acceptance pattern (18.x — Settings):** Contract: `docs/api/endpoints/settings.md`, `docs/api/schemas/settings.md`. Scramble group **Settings** (weight: 15). Feature module: `app/Features/Settings/`. Routes split across `auth.php` (`/me/settings`), `tenancy.php` (`/workspace/settings`), `admin.php` (`/admin/settings`). PATCH is partial update. Workspace PATCH requires `writable.subscription`.

**Role matrix:**

| Endpoint | Read | Write |
|----------|------|-------|
| `/me/settings` | Self | Self |
| `/workspace/settings` | Any member | Owner/admin |
| `/admin/settings` | Super-admin | Super-admin |

### Task 18.1 — User settings migration

- **Est.:** XS
- **Depends on:** 0.1
- **Files:** migration adding to `users`: `timezone` (varchar 64, default `UTC`), `locale` (varchar 10, default `en`), `notification_preferences` (jsonb, default all `true`)
- **Done when:** `migrate:fresh` succeeds; defaults applied on existing users.
- **Tests:** Migration smoke via model factory.
- **Edge cases:** Invalid json default uses Laravel `json` cast default array.

### Task 18.2 — Workspace settings migration

- **Est.:** XS
- **Depends on:** 1.2
- **Files:** migration adding to `freelancers`: `default_currency` (char 3, default `BDT`), `invoice_number_prefix` (varchar 20, default `INV`), `default_tax_rate` (decimal 5,2 nullable), `invoice_footer_notes` (text nullable), `business_name`, `business_email`, `business_address` (nullable)
- **Done when:** `migrate:fresh` succeeds; existing freelancers get defaults.
- **Tests:** Covered by Freelancer model tests in 18.5.
- **Edge cases:** Prefix max length enforced at validation layer (18.8).

### Task 18.3 — Platform settings table and seeder

- **Est.:** S
- **Depends on:** 1.23
- **Files:** `platform_settings` migration (singleton `id = 1`), `PlatformSettings` model, `PlatformSettingsSeeder` — `default_trial_days` 14, `default_plan_slug` `starter`, `support_email` from config, `maintenance_mode` false
- **Done when:** Seeder creates row id 1; re-run idempotent.
- **Tests:** Unit — seeder creates expected defaults.
- **Edge cases:** `default_plan_slug` must match seeded plan.

### Task 18.4 — PlatformSettingsService and cache

- **Est.:** S
- **Depends on:** 18.3, 2.13
- **Files:** `app/Features/Settings/Services/PlatformSettingsService.php`, `app/Services/Cache/PlatformSettingsCache.php`
- **Methods:** `get()`, `update(array)`, `defaultTrialDays(): int`, `forgetCache()`
- **Done when:** Cached 5 min; forget on update.
- **Tests:** Unit — cache hit/miss; update invalidates.
- **Edge cases:** Missing row falls back to seeder defaults.

### Task 18.5 — User and workspace settings services

- **Est.:** S
- **Depends on:** 18.1, 18.2
- **Files:** `UserSettingsService`, `WorkspaceSettingsService`, `NotificationPreferences` value object or cast on `User`
- **Actions:** validate timezone against `timezone_identifiers_list()`; locale allowlist `en`; merge partial `notification_preferences`
- **Done when:** Services read/update user and freelancer columns; User model casts preferences.
- **Tests:** Unit — defaults, partial merge, invalid timezone rejected.
- **Edge cases:** Unknown notification keys stripped on save.

### Task 18.6 — UserSettingsResource and GET/PATCH /me/settings

- **Est.:** M
- **Depends on:** 18.5
- **Files:** `UserSettingsController`, `ShowUserSettingsRequest`, `UpdateUserSettingsRequest`, `UserSettingsResource`; routes in `routes/features/v1/auth.php`
- **Done when:** Authenticated user can read and patch own settings; path in `DocumentationTest`.
- **Tests:** Feature — 200 shape; invalid timezone → 422; unauthenticated → 401.
- **Edge cases:** PATCH with empty body returns current settings unchanged.

### Task 18.7 — WorkspaceSettingsPolicy

- **Est.:** XS
- **Depends on:** 2.5, 18.5
- **Files:** `WorkspaceSettingsPolicy` using `AuthorizesTenantMembership` — `view` = member, `update` = `canManageClientsAndProjects()`
- **Done when:** Policy registered; member can view, member cannot update.
- **Tests:** Unit or feature — matrix per role.
- **Edge cases:** Super-admin with tenant context bypasses membership (read/write).

### Task 18.8 — WorkspaceSettingsResource and GET/PATCH /workspace/settings

- **Est.:** M
- **Depends on:** 18.5, 18.7, 2.4
- **Files:** `WorkspaceSettingsController`, requests, `WorkspaceSettingsResource`; routes in `routes/features/v1/tenancy.php`
- **Middleware:** GET — `freelancer.context`; PATCH — add `writable.subscription`
- **Done when:** Member read 200; owner/admin patch 200; member patch 403; read-only sub patch 403.
- **Tests:** Feature — role matrix; path in `DocumentationTest`.
- **Edge cases:** Invalid prefix characters → 422.

### Task 18.9 — PlatformSettingsResource and GET/PATCH /admin/settings

- **Est.:** M
- **Depends on:** 18.4, 3.2
- **Files:** `PlatformSettingsController`, requests, `PlatformSettingsResource`; routes in `routes/features/v1/admin.php`
- **Done when:** Super-admin read/patch 200; regular user 403; cache invalidated on patch.
- **Tests:** Feature — admin 200; user 403; invalid plan slug → 422.
- **Edge cases:** `maintenance_mode` stored only — no middleware enforcement yet.

### Task 18.10 — Invoice numbering uses workspace prefix

- **Est.:** S
- **Depends on:** 18.2, 8.2
- **Files:** update `ClientInvoiceService::generateInvoiceNumber()` to load freelancer `invoice_number_prefix`
- **Done when:** Prefix `ACME` yields `ACME-2026-0001`; default `INV` unchanged for existing workspaces.
- **Tests:** Unit — custom prefix sequence; year rollover.
- **Edge cases:** Prefix with hyphen handled in regex; unique per freelancer unchanged.

### Task 18.11 — Default currency from workspace settings

- **Est.:** S
- **Depends on:** 18.2, 5.2, 8.2
- **Files:** `StoreProjectRequest` / create action and `StoreClientInvoiceRequest` / create action — when `currency` omitted, resolve from active freelancer `default_currency`
- **Done when:** Omitted currency uses workspace default; explicit body value wins.
- **Tests:** Feature — create project/invoice without currency uses workspace `default_currency`.
- **Edge cases:** Do not accept workspace default via request body — only internal fallback.

### Task 18.12 — Onboarding uses platform trial default

- **Est.:** XS
- **Depends on:** 18.4, 3.5
- **Files:** `FreelancerOnboardingService` — replace `DEFAULT_TRIAL_DAYS` constant with `PlatformSettingsService::defaultTrialDays()`; per-request `trial_days` still overrides
- **Done when:** Onboarding without `trial_days` uses platform setting; admin PATCH updates future onboardings.
- **Tests:** Feature or unit — custom platform default reflected in new subscription.
- **Edge cases:** Cache stale max 5 min after platform PATCH.

### Task 18.13 — Notification preference gates

- **Est.:** S
- **Depends on:** 18.5, 15.13
- **Files:** `User::prefersNotification(string $key): bool` with role gates; apply in `TrialEndingSoonNotification::via()` and other Phase 15 subscription mails
- **Done when:** Owner with `subscription_alerts: false` does not receive trial/payment mails; `true` receives them.
- **Tests:** Feature — `Notification::fake()` with preference off → not sent.
- **Edge cases:** Non-owner never receives subscription alerts regardless of preference.

### Task 18.14 — Embed user settings summary on GET /me

- **Est.:** S
- **Depends on:** 18.6, 9.2
- **Files:** `MeResource` — add `user_settings: { timezone, locale }`; update `docs/api/endpoints/me.md` and `docs/api/schemas/membership.md`
- **Done when:** `GET /me` includes timezone/locale without extra round-trip.
- **Tests:** Feature — me response includes `user_settings` keys.
- **Edge cases:** Full `notification_preferences` remain on `/me/settings` only.

### Task 18.15 — Settings isolation and documentation tests

- **Est.:** S
- **Depends on:** 18.6, 18.8, 18.9
- **Files:** `tests/Feature/Settings/*`; update `DocumentationTest` for all six settings paths
- **Done when:** All settings paths in OpenAPI spec; contract markdown aligned with Resources.
- **Tests:** Feature — doc test; workspace settings require valid `X-Freelancer-Id` for multi-membership user.
- **Edge cases:** Cross-tenant N/A — settings resolved from context, not route IDs.

---

## Phase 19 — Reporting (workspace stats, saved reports, async exports)

Dashboard stats, filterable reports, saved report definitions, and async CSV exports for **freelancer workspaces** and **super-admin platform** scope. Reuses SQL aggregate pattern from Task 7.6; heavy exports run in queue (aligns with Phase 17.4 design — implemented here, scale infra in Phase 17).

**Phase depends on:** 7.6 (project time-summary SQL pattern), 8.1 (invoice aggregates), 11.1 (isolation test patterns), 13.4 (owner/admin vs member for writes), 15.4 (`SubscriptionCharge` for revenue reports), 3.10 (`AdminActivityLogger` for platform exports).

**Deep reference:** Report catalog and schema in [architecture-overview.md — Reporting model](./architecture-overview.md#reporting-model-phase-19) · ERD §8 in [database-erd.md](./database-erd.md).

**Acceptance pattern (19.x — Reporting):** Feature module `app/Features/Reporting/`. Route file `routes/features/v1/reporting.php`. Scramble group **Reports** (weight: 45). Contract: `docs/api/endpoints/reports.md`, `docs/api/endpoints/admin-reports.md`, `docs/api/schemas/report.md`. Aggregates via SQL only — never load full `time_logs` into memory. Export files on disk (`storage/app/report-exports/`) — not DB blobs. Cross-tenant filter IDs (e.g. another workspace's `client_id`) → **404** `not_found`.

**Role matrix:**

| Endpoint group | Read (run / show / list) | Write (save / export / delete) |
|----------------|--------------------------|--------------------------------|
| `/workspace/stats`, `/reports/run`, `/reports`, `/report-exports` | Any workspace member | Owner/admin only |
| `/admin/reports/*`, `/admin/report-exports/*` | Super-admin | Super-admin |

**Report types (`ReportType` enum):**

| Value | Scope | Used by |
|-------|-------|---------|
| `workspace_overview` | Workspace | `GET /workspace/stats` |
| `time_logs` | Workspace | `/reports/run`, exports |
| `unbilled_work` | Workspace | `/reports/run`, exports |
| `client_invoices` | Workspace | `/reports/run`, exports |
| `platform_overview` | Platform | `GET /admin/reports/platform-stats` |
| `freelancer_list` | Platform | `/admin/reports/run`, exports |
| `subscription_revenue` | Platform | `/admin/reports/run`, exports |

**Filter JSON keys (per type):** `from`, `to` (date), `client_id`, `project_id`, `user_id`, `status`, `plan_id` — validated in `ReportFilterValidator`; unknown keys stripped.

### Task 19.1 — Reporting enums

- **Est.:** XS
- **Depends on:** 0.1
- **Files:** `app/Features/Reporting/Enums/ReportType.php`, `ReportScope.php` (`Workspace`, `Platform`), `ExportFormat.php` (`Csv`; `Pdf` reserved), `ExportStatus.php` (`Pending`, `Processing`, `Completed`, `Failed`)
- **Done when:** Enums match report catalog above; unit test asserts scope mapping per type.
- **Tests:** Unit — each `ReportType` maps to correct `ReportScope`.
- **Edge cases:** `Pdf` format rejected at validation until Task 19.26 documents otherwise.

### Task 19.2 — saved_reports migration

- **Est.:** S
- **Depends on:** 1.2, 0.1
- **Schema:** `id`, `freelancer_id` (FK nullable — null = platform-scoped), `created_by_user_id` (FK users), `name` (varchar 255), `report_type` (string), `filters` (jsonb, default `{}`), `timestamps`; index `(freelancer_id, created_by_user_id)`
- **Done when:** `migrate:fresh` succeeds; platform rows have `freelancer_id` null.
- **Tests:** Factory creates tenant and platform saved reports.
- **Edge cases:** Cascade delete when freelancer deleted (or restrict — document choice in migration).

### Task 19.3 — report_exports migration

- **Est.:** S
- **Depends on:** 19.2
- **Schema:** `id`, `freelancer_id` (FK nullable), `saved_report_id` (FK nullable), `requested_by_user_id` (FK), `report_type`, `filters` (jsonb), `format`, `status`, `file_path` (nullable), `row_count` (nullable int), `error_message` (nullable text), `expires_at`, `completed_at` (nullable), `timestamps`; index `(freelancer_id, requested_by_user_id, status)`
- **Done when:** `migrate:fresh` succeeds.
- **Tests:** Factory with each `ExportStatus`.
- **Edge cases:** `file_path` null until job completes.

### Task 19.4 — SavedReport and ReportExport models

- **Est.:** S
- **Depends on:** 19.1 – 19.3
- **Files:** `SavedReport`, `ReportExport` models; factories under `database/factories/Reporting/`; casts for enums and `filters` array
- **Done when:** Relationships: `freelancer()`, `createdBy()` / `requestedBy()`, `savedReport()`; tenant scope helper on queries where applicable.
- **Tests:** Unit — casts, factory states.
- **Edge cases:** Platform export has null `freelancer_id`.

### Task 19.5 — ReportQueryService (workspace overview)

- **Est.:** M
- **Depends on:** 19.1, 4.2, 7.1, 8.1
- **Files:** `app/Features/Reporting/Services/ReportQueryService.php`
- **Returns:** Active client count, active project count, hours logged current calendar month, total unbilled hours, outstanding invoice total (sent + overdue, not draft)
- **Actions:** SQL `SUM` / `COUNT` with tenant global scopes — same pattern as Task 7.6
- **Done when:** Empty workspace returns zeros; unit test asserts SQL aggregates without loading all logs.
- **Tests:** Unit — zero data; seeded workspace with logs and invoices.
- **Edge cases:** Soft-deleted projects excluded from active count.

### Task 19.6 — ReportQueryService (platform overview)

- **Est.:** M
- **Depends on:** 19.1, 3.3, 15.4
- **Returns:** Freelancers by status, subscriptions by plan/status, trials ending within 7 days count
- **Actions:** Unscoped platform queries on `freelancers`, `subscriptions` — admin-only service
- **Done when:** Counts match seeded data.
- **Tests:** Unit — status breakdown; no tenant header required.
- **Edge cases:** Freelancer without subscription counted separately.

### Task 19.7 — GET /workspace/stats

- **Est.:** S
- **Depends on:** 19.5
- **Files:** `WorkspaceStatsController`, `ShowWorkspaceStatsRequest`, `WorkspaceStatsResource`; route in `reporting.php`
- **Middleware:** `auth:sanctum`, `freelancer.context`
- **Done when:** 200 returns `WorkspaceStatsResource`; path in `DocumentationTest`.
- **Tests:** Feature — member 200; 401; cross-tenant header 404/403 per middleware.
- **Edge cases:** Read allowed when subscription read-only.

### Task 19.8 — GET /admin/reports/platform-stats

- **Est.:** S
- **Depends on:** 19.6
- **Files:** `AdminPlatformStatsController`, `PlatformStatsResource`; route under `admin` prefix in `reporting.php`
- **Middleware:** `auth:sanctum`, `can:super-admin`
- **Done when:** 200 for super-admin; 403 non-admin; path in `DocumentationTest`.
- **Tests:** Feature — auth matrix.
- **Edge cases:** No `X-Freelancer-Id` required.

### Task 19.9 — Reporting route file registration

- **Est.:** XS
- **Depends on:** 19.7, 19.8
- **Files:** `routes/features/v1/reporting.php`; `require` in `routes/api.php`
- **Done when:** Routes resolve under `/api/v1`; Scramble `#[Group('Reports', weight: 45)]` on controllers.
- **Tests:** `route:list` smoke; DocumentationTest paths for stats endpoints.
- **Edge cases:** Tenant controllers include `X-Freelancer-Id` header attribute.

### Task 19.10 — ReportFilterValidator

- **Est.:** M
- **Depends on:** 19.1, 4.2, 8.1
- **Files:** `ReportFilterValidator` — per-type allowed keys, date order, tenant-owned `client_id` / `project_id` existence
- **Done when:** Invalid filter → validation exception; foreign tenant IDs → 404 via existence check scoped to tenant.
- **Tests:** Unit — each report type; cross-tenant client_id rejected.
- **Edge cases:** `from` > `to` → 422.

### Task 19.11 — RunReportAction (workspace types)

- **Est.:** M
- **Depends on:** 19.5, 19.10
- **Types:** `time_logs`, `unbilled_work`, `client_invoices`
- **Returns:** `{ summary: {...}, preview: CursorPaginator }` — preview uses existing Resource shapes where possible (`TimeLogResource`, `ClientInvoiceResource`)
- **Done when:** Summary totals match SQL; preview cursor paginated max 100.
- **Tests:** Unit — summary math; Feature — run with filters.
- **Edge cases:** Empty result set returns empty preview, zero summary.

### Task 19.12 — POST /reports/run

- **Est.:** S
- **Depends on:** 19.11
- **Files:** `RunReportController`, `RunReportRequest`, `ReportRunResource`
- **Body:** `report_type`, `filters` (object), optional `per_page`, `cursor`
- **Done when:** Member can run; path in `DocumentationTest`.
- **Tests:** Feature — 200 shape; invalid type 422; member OK; owner/admin OK.
- **Edge cases:** `workspace_overview` via this endpoint optional — prefer `GET /workspace/stats`.

### Task 19.13 — RunReportAction (platform types)

- **Est.:** M
- **Depends on:** 19.6, 19.10
- **Types:** `freelancer_list`, `subscription_revenue`
- **Done when:** Preview paginates; revenue summary sums paid charges only.
- **Tests:** Unit — revenue SUM; Feature — admin run.
- **Edge cases:** Date filter on `paid_at` for charges.

### Task 19.14 — POST /admin/reports/run

- **Est.:** S
- **Depends on:** 19.13
- **Files:** `AdminRunReportController`, reuse `RunReportRequest` / `ReportRunResource` with platform guard
- **Done when:** Super-admin 200; non-admin 403.
- **Tests:** Feature — auth matrix; path in `DocumentationTest`.
- **Edge cases:** Platform filters ignore `X-Freelancer-Id`.

### Task 19.15 — SavedReportPolicy and ReportExportPolicy

- **Est.:** S
- **Depends on:** 19.4, 13.4
- **Rules:** Any member view/run; owner/admin create/update/delete saved reports and queue exports; platform rows super-admin only
- **Done when:** Policy methods match role matrix above.
- **Tests:** Unit — member read, member write denied, owner write allowed.
- **Edge cases:** User cannot access another tenant's saved report → 404.

### Task 19.16 — Saved reports CRUD (workspace)

- **Est.:** M
- **Depends on:** 19.15
- **Routes:** `GET/POST /reports`, `GET/PATCH/DELETE /reports/{report}`
- **Done when:** Cursor list; CRUD respects policy; paths in `DocumentationTest`.
- **Tests:** Feature — CRUD matrix; cross-tenant 404.
- **Edge cases:** PATCH partial — name and/or filters.

### Task 19.17 — Saved reports CRUD (admin)

- **Est.:** M
- **Depends on:** 19.15
- **Routes:** `GET/POST /admin/reports`, `GET/PATCH/DELETE /admin/reports/{report}` — always `freelancer_id` null
- **Done when:** Super-admin only; paths in `DocumentationTest`.
- **Tests:** Feature — 403 non-admin; platform saved report list.
- **Edge cases:** Tenant user cannot hit admin routes.

### Task 19.18 — ReportExportService and storage disk

- **Est.:** M
- **Depends on:** 19.4, 19.11, 19.13
- **Files:** `ReportExportService`, `config/filesystems.php` disk `report-exports` → `storage/app/report-exports`
- **Actions:** Create pending row; stream CSV in chunks; set `file_path`, `row_count`, `expires_at` (+7 days)
- **Done when:** CSV written without loading all rows; unit test with `Storage::fake()`.
- **Tests:** Unit — CSV headers match report type; large dataset uses chunk/cursor.
- **Edge cases:** Failed write sets `Failed` status and `error_message`.

### Task 19.19 — GenerateReportExportJob and ReportReadyNotification

- **Est.:** M
- **Depends on:** 19.18
- **Files:** `GenerateReportExportJob`, `ReportReadyNotification` (queued mail, link to download)
- **Done when:** Job transitions status; notification sent on success.
- **Tests:** Feature — `Queue::fake()` dispatch; notification content has export id.
- **Edge cases:** Job idempotent if already completed.

### Task 19.20 — Report exports API (workspace)

- **Est.:** M
- **Depends on:** 19.19, 19.15
- **Routes:** `POST /report-exports`, `GET /report-exports`, `GET /report-exports/{export}` — completed export returns time-limited download URL (signed route)
- **Body (POST):** `report_type`, `filters`, `format` (`csv`), optional `saved_report_id`
- **Done when:** Owner/admin can queue; member read-only on own exports list; paths in `DocumentationTest`.
- **Tests:** Feature — queue → job → completed download; member POST 403.
- **Edge cases:** Expired export → 410 or 404 with `export_expired` code.

### Task 19.21 — Report exports API (admin)

- **Est.:** M
- **Depends on:** 19.19, 19.15
- **Routes:** `POST/GET /admin/report-exports`, `GET /admin/report-exports/{export}`
- **Done when:** Super-admin queue and download; `AdminActivityLogger` on POST with action `report_export_queued`.
- **Tests:** Feature — activity log row; 403 non-admin.
- **Edge cases:** Platform export has null `freelancer_id`.

### Task 19.22 — PurgeExpiredReportExports command

- **Est.:** S
- **Depends on:** 19.18
- **Files:** `app/Features/Reporting/Console/PurgeExpiredReportExportsCommand.php`; schedule daily in `routes/console.php`
- **Done when:** Deletes rows where `expires_at` < now and removes files from disk.
- **Tests:** Feature — expired row removed; file deleted.
- **Edge cases:** Missing file on disk still deletes row.

### Task 19.23 — Reporting isolation feature tests

- **Est.:** M
- **Depends on:** 19.12, 19.14, 19.16, 19.20
- **Files:** `tests/Feature/Reporting/ReportingIsolationTest.php`
- **Cases:** Workspace A filters with B's `client_id` → 404; saved report cross-tenant → 404; non-admin admin routes → 403
- **Done when:** All cases pass; mirrors Phase 11 style.
- **Tests:** Feature — dedicated file.
- **Edge cases:** Super-admin override does not apply to tenant report run without membership.

### Task 19.24 — API contract markdown and schema

- **Est.:** M
- **Depends on:** 19.7 – 19.22
- **Files:** `docs/api/endpoints/reports.md`, `admin-reports.md`, `schemas/report.md`; update `docs/api/README.md` endpoint index — **design stubs exist pre-build; verify against Resources on completion**
- **Done when:** Every route documented with auth, middleware, request, response, errors tables; matches shipped Resources.
- **Tests:** Manual cross-check against Resources after implementation.
- **Edge cases:** Document `export_expired` error in `errors.md` if implemented.

### Task 19.25 — DocumentationTest and Scramble coverage

- **Est.:** S
- **Depends on:** 19.9, 19.24
- **Files:** `tests/Feature/Api/DocumentationTest.php` — all reporting paths; `api-scramble-docs.mdc` Reports group weight 45
- **Done when:** OpenAPI lists all Phase 19 paths; group order stable.
- **Tests:** Feature — DocumentationTest green.
- **Edge cases:** Admin and tenant paths both registered.

### Task 19.26 — Architecture and ERD documentation

- **Est.:** S
- **Depends on:** 19.2, 19.3
- **Files:** `docs/project-structure/feature-based-architecture.md`, `docs/multi-tenant/database-erd.md`, `docs/multi-tenant/architecture-overview.md` — **pre-aligned in spec pass; re-verify when migrations land**
- **Done when:** Module import rules and ERD match final migrations.
- **Tests:** N/A — doc review on PR.
- **Edge cases:** Phase 17.6 note if tenant-wide log reports need `freelancer_id` on `time_logs`.

---

## Phase 16 — Platform growth (future)

| Task | Description |
|------|-------------|
| 16.1 | Subdomain routing (`{slug}.lancehive.com`) |
| 16.2 | Audit log per tenant (freelancer-facing activity UI; builds on admin log pattern) |
| 16.3 | Self-service freelancer signup |
| 16.4 | ClientInvoice PDF export (store on disk/S3 — path column, not DB blob) |
| 16.5 | Generate ClientInvoiceItems from time logs (hours × project.hourly_rate) |
| 16.6 | bKash payment integration for ClientInvoicePayment |
| 16.7 | Bank transfer verification workflow |

---

## Phase 17 — Scale (future — do not build in MVP)

Trigger when metrics justify (slow lists, `time_logs` > ~500k, heavy reporting). See [design-rationale-and-scaling.md §2](./design-rationale-and-scaling.md#2-growth-expectations-no-big-scale-build-now).

| Task | Description |
|------|-------------|
| 17.1 | Archive job — move `time_logs` older than N years to `time_logs_archive` or cold storage |
| 17.2 | Read replica for report/export queries |
| 17.3 | Optional `time_logs` partition by `logged_at` (PostgreSQL) |
| 17.4 | Async report generation — **core flow in Phase 19**; scale hardening (read replica routing, larger exports) stays here |
| 17.5 | Per-tenant rate limiting on write APIs |
| 17.6 | Optional denormalized `freelancer_id` on `time_logs` if tenant-wide log reports need it |

---

## Suggested PR grouping

Respect **build order** within each PR — e.g. PR 5 must merge invoice items (1.21) before time logs (1.16).

| PR | Tasks | Title |
|----|-------|-------|
| 1 | 1.1 – 1.6 | Freelancer workspace tables and models |
| 2 | 1.7 – 1.12 | Client and project tables and models |
| 3 | 1.13 – 1.15 | Task tables and model |
| 4 | 1.18 – 1.22 | ClientInvoice, ClientInvoiceItem, ClientInvoicePayment models |
| 5 | 1.16 – 1.17 | Time log table and model (after 1.21) |
| 6 | 1.23 – 1.28 | Plan, Subscription, SubscriptionCharge + indexes |
| 7 | 2.1 – 2.14 | Tenant context, scoping, policies, API conventions, Redis cache |
| 8 | 3.1 – 3.10 | Super-admin onboarding + admin activity log |
| 9 | 4.1 – 4.7 | Client CRUD API |
| 10 | 5.1 – 5.7 | Project CRUD API |
| 11 | 6.1 – 6.6 | Task CRUD API |
| 12 | 7.1 – 7.6 | Time logging API |
| 13 | 8.1 – 8.9 | ClientInvoice API (manual ClientInvoicePayment) |
| 14 | 9.1 – 9.3 | Enhanced /me |
| 15 | 10.1 – 10.2 | Seed data and dev docs |
| 16 | 11.1 – 11.5 | Tenant isolation tests |
| 17 | 15.1 – 15.14 | Platform subscriptions (Stripe) |
| 18 | 18.1 – 18.15 | Settings (user, workspace, platform) |
| 19 | 19.1 – 19.10 | Reporting foundation (stats endpoints) |
| 20 | 19.11 – 19.15 | Ad-hoc report run |
| 21 | 19.16 – 19.17 | Saved reports |
| 22 | 19.18 – 19.22 | Async CSV exports |
| 23 | 19.23 – 19.26 | Reporting isolation tests and docs |
| 24+ | 12 – 14, 16 | Role cleanup, team, portal, growth |

---

## Task checklist

Progress legend: `[x]` done · `[ ]` not started. **Last verified:** 2026-09-11 (Phase 19 reporting complete).

```
Phase 0
[x] 0.1  Verify local environment

Phase 1 — Database ✅
[x] 1.1  Freelancer status enum
[x] 1.2  Freelancers migration
[x] 1.3  Freelancer model
[x] 1.4  Membership role enum
[x] 1.5  Memberships migration
[x] 1.6  FreelancerMembership model
[x] 1.7  Client status enum
[x] 1.8  Clients migration
[x] 1.9  Client model
[x] 1.10 Project status enum (active/on_hold/completed)
[x] 1.11 Projects migration (hourly_rate, deadline, softDeletes)
[x] 1.12 Project model
[x] 1.13 Task status enum
[x] 1.14 Tasks migration (softDeletes)
[x] 1.15 Task model
[x] 1.18 ClientInvoice status enum
[x] 1.19 client_invoices migration (identity fields, softDeletes)
[x] 1.20 ClientInvoice model
[x] 1.21 client_invoice_items migration
[x] 1.22 ClientInvoiceItem + ClientInvoicePayment models
[x] 1.16 Time logs migration (after 1.21)
[x] 1.17 TimeLog model
[x] 1.23 Plan model (limits, is_custom, BDT)
[x] 1.24 Subscription enums (incl. ReadOnly)
[x] 1.25 Subscriptions migration
[x] 1.26 Subscription model
[x] 1.27 SubscriptionCharge model
[x] 1.28 Performance indexes migration

Phase 2 — Tenant isolation ✅
[x] 2.1  TenantContext service
[x] 2.2  BelongsToFreelancer trait
[x] 2.3  BelongsToTenantViaProject trait
[x] 2.4  EnsureFreelancerContext middleware
[x] 2.5  FreelancerPolicy
[x] 2.6  ClientPolicy
[x] 2.7  ProjectPolicy
[x] 2.8  TaskPolicy
[x] 2.9  TimeLogPolicy
[x] 2.10 ClientInvoicePolicy
[x] 2.11 Service layer boundaries
[x] 2.12 Tenant API conventions (cursor pagination)
[x] 2.13 Redis cache configuration
[x] 2.14 Cache services (plans, subscription, memberships)

Phase 3 — Super-admin onboarding ✅
[x] 3.1  Freelancer API resource
[x] 3.2  Admin route group
[x] 3.3  List freelancers (GET /admin/freelancers)
[x] 3.4  Show freelancer (GET /admin/freelancers/{id})
[x] 3.5  FreelancerOnboardingService
[x] 3.6  Create freelancer (POST /admin/freelancers)
[x] 3.7  Update freelancer status (PATCH /admin/freelancers/{id})
[x] 3.8  Freelancer invite notification
[x] 3.9  Resend invite (POST /admin/freelancers/{id}/resend-invite)
[x] 3.10 Admin activity log

Phase 4 — Clients ✅
[x] 4.1  Client API resource
[x] 4.2  Tenant-scoped route group
[x] 4.3  Create client (POST /clients)
[x] 4.4  List clients (GET /clients)
[x] 4.5  Show client (GET /clients/{id})
[x] 4.6  Update client (PATCH /clients/{id})
[x] 4.7  Archive client (DELETE /clients/{id})

Phase 5 — Projects ✅
[x] 5.1  Project API resource
[x] 5.2  Create project (POST /clients/{client}/projects)
[x] 5.3  List client projects (GET /clients/{client}/projects)
[x] 5.4  List all tenant projects (GET /projects)
[x] 5.5  Show project (GET /projects/{id})
[x] 5.6  Update project (PATCH /projects/{id})
[x] 5.7  Soft-delete project (DELETE /projects/{id})

Phase 6 — Tasks ✅
[x] 6.1  Task API resource
[x] 6.2  Create task (POST /projects/{project}/tasks)
[x] 6.3  List project tasks (GET /projects/{project}/tasks)
[x] 6.4  Show task (GET /tasks/{id})
[x] 6.5  Update task (PATCH /tasks/{id})
[x] 6.6  Soft-delete task (DELETE /tasks/{id})

Phase 7 — Time logs ✅
[x] 7.1  TimeLog API resource
[x] 7.2  Log time (POST /tasks/{task}/time-logs)
[x] 7.3  List task time logs (GET /tasks/{task}/time-logs)
[x] 7.4  Update time log (PATCH /time-logs/{id})
[x] 7.5  Delete time log (DELETE /time-logs/{id})
[x] 7.6  Project time summary (GET /projects/{project}/time-summary)

Phase 8 — Client invoicing ✅
[x] 8.1  ClientInvoice & ClientInvoiceItem API resources
[x] 8.2  Create client invoice (POST /projects/{project}/client-invoices)
[x] 8.3  Add invoice item (POST /client-invoices/{id}/items)
[x] 8.4  List project invoices (GET /projects/{project}/client-invoices)
[x] 8.5  Show client invoice (GET /client-invoices/{id})
[x] 8.6  Update client invoice (PATCH /client-invoices/{id})
[x] 8.7  Record client payment (POST /client-invoices/{id}/payments)
[x] 8.8  Soft-delete client invoice (DELETE /client-invoices/{id})
[x] 8.9  Mark overdue job

Phase 9 — Auth /me ✅
[x] 9.1  FreelancerMembershipResource
[x] 9.2  Enhance GET /me
[x] 9.3  Workspace switch via header

Phase 10 — Seed data ✅
[x] 10.1 Update seeders
[x] 10.2 Dev credentials docs

Phase 11 — Isolation tests ✅
[x] 11.1 Client isolation tests
[x] 11.2 Project isolation tests
[x] 11.3 Task and time log isolation tests
[x] 11.4 ClientInvoice isolation tests
[x] 11.5 Super-admin tenant override tests

Phase 12 — Role cleanup (optional) ✅
[x] 12.1 Add UserRole::User
[x] 12.2 Map seed users to memberships
[x] 12.3 Deprecate global role helpers

Phase 13 — Freelancer team ✅
[x] 13.1 ClientMembership table stub
[x] 13.2 Invite freelancer member
[x] 13.3 List/remove workspace members
[x] 13.4 Role-based permission tightening

Phase 14 — Client portal ✅
[x] 14.1 ClientMembership model
[x] 14.2 EnsureClientContext middleware
[x] 14.3 Invite client member
[x] 14.4 Portal read API (clients, projects, invoices)
[x] 14.5 Enhance /me with client memberships

Phase 15 — Platform subscriptions ✅
[x] 15.1  Install Cashier / Stripe
[x] 15.2  Sync plans with Stripe prices
[x] 15.3  Admin plan CRUD
[x] 15.4  SubscriptionService
[x] 15.5  GET /subscription
[x] 15.6  POST /subscription/checkout
[x] 15.7  POST /subscription/swap
[x] 15.8  POST /subscription/cancel
[x] 15.9  Stripe webhooks
[x] 15.10 EnsureWritableSubscription middleware (read-only mode)
[x] 15.11 Plan limit hardening (lockForUpdate)
[x] 15.12 Admin assign custom plan
[x] 15.13 Subscription emails
[x] 15.14 Subscription tests

Phase 16 — Future
[ ] 16.x Platform growth

Phase 18 — Settings ✅
[x] 18.1  User settings migration
[x] 18.2  Workspace settings migration
[x] 18.3  Platform settings table and seeder
[x] 18.4  PlatformSettingsService and cache
[x] 18.5  User and workspace settings services
[x] 18.6  GET/PATCH /me/settings
[x] 18.7  WorkspaceSettingsPolicy
[x] 18.8  GET/PATCH /workspace/settings
[x] 18.9  GET/PATCH /admin/settings
[x] 18.10 Invoice numbering uses workspace prefix
[x] 18.11 Default currency from workspace settings
[x] 18.12 Onboarding uses platform trial default
[x] 18.13 Notification preference gates
[x] 18.14 Embed user settings summary on GET /me
[x] 18.15 Settings isolation and documentation tests

Phase 19 — Reporting ✅
[x] 19.1  Reporting enums
[x] 19.2  saved_reports migration
[x] 19.3  report_exports migration
[x] 19.4  SavedReport and ReportExport models
[x] 19.5  ReportQueryService (workspace overview)
[x] 19.6  ReportQueryService (platform overview)
[x] 19.7  GET /workspace/stats
[x] 19.8  GET /admin/reports/platform-stats
[x] 19.9  Reporting route file registration
[x] 19.10 ReportFilterValidator
[x] 19.11 RunReportAction (workspace types)
[x] 19.12 POST /reports/run
[x] 19.13 RunReportAction (platform types)
[x] 19.14 POST /admin/reports/run
[x] 19.15 SavedReportPolicy and ReportExportPolicy
[x] 19.16 Saved reports CRUD (workspace)
[x] 19.17 Saved reports CRUD (admin)
[x] 19.18 ReportExportService and storage disk
[x] 19.19 GenerateReportExportJob and ReportReadyNotification
[x] 19.20 Report exports API (workspace)
[x] 19.21 Report exports API (admin)
[x] 19.22 PurgeExpiredReportExports command
[x] 19.23 Reporting isolation feature tests
[x] 19.24 API contract markdown and schema
[x] 19.25 DocumentationTest and Scramble coverage
[x] 19.26 Architecture and ERD documentation
```
