# Implementation Tasks

Small, ordered tasks for building the multi-tenant freelancer platform. Complete each task fully (including tests where noted) before moving to the next.

**Legend**

- **Depends on:** tasks that must be done first
- **Done when:** acceptance criteria for the task
- **Est.:** rough effort (XS ≈ 30 min, S ≈ 1 hr, M ≈ 2 hr)
- **API paths:** all routes live under **`/api/v1`** (e.g. `POST /clients` → `POST /api/v1/clients`). Configure via `API_ROUTE_VERSION` in `.env`.
- **Route files:** `routes/features/v1/{feature}.php` — required from `routes/api.php` via `config('api.features_routes')`.
- **Domain files:** `app/Features/{Feature}/` — no `V1/` subfolder; models, actions, controllers are version-agnostic until v2 breaks a contract.
- **Tests:** `tests/Feature/{Feature}/` + `$this->apiUrl()` — not `tests/Feature/V1/`.

See [architecture-overview.md](./architecture-overview.md) for full entity schemas and subscription design. Scale-smooth MVP rules: [design-rationale-and-scaling.md](./design-rationale-and-scaling.md).

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

---

## Phase 1 — Database foundation

Build tables and models one at a time. No API yet.

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
- **Depends on:** 1.2
- **Schema:** `id`, `freelancer_id`, `user_id`, `role`, `timestamps`, unique `(freelancer_id, user_id)`

### Task 1.6 — FreelancerMembership model

- **Est.:** S
- **Depends on:** 1.4, 1.5
- **Actions:** User ↔ Freelancer many-to-many via `freelancerMemberships()` / `memberships()`

### Client (Tasks 1.7 – 1.9)

### Task 1.7 — Client status enum

- **Est.:** XS
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

### TimeLog (Tasks 1.16 – 1.17)

### Task 1.16 — Time logs table migration

- **Est.:** XS
- **Depends on:** 1.14
- **Schema:**
  - `id`, `task_id` (FK), `user_id` (FK), `hours` (decimal 8,2), `description` (nullable), `logged_at` (datetime), `client_invoice_item_id` (FK nullable), `timestamps`
  - Index on `(task_id, user_id)`

### Task 1.17 — TimeLog model

- **Est.:** S
- **Depends on:** 1.16
- **Actions:** `task()` belongsTo; `user()` belongsTo; `Task` hasMany `timeLogs()`; `User` hasMany `timeLogs()`

### Client invoicing (Tasks 1.18 – 1.22)

Models: `ClientInvoice`, `ClientInvoiceItem`, `ClientInvoicePayment` (see architecture-overview.md).

### Task 1.18 — ClientInvoice status enum

- **Est.:** XS
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

### Platform subscriptions (Tasks 1.23 – 1.27)

### Task 1.23 — Plan model and migration

- **Est.:** S
- **Schema:** `id`, `name`, `slug` (unique), `price_monthly`, `price_yearly`, `currency` (default `BDT`), `max_clients`, `max_projects`, `max_team_members` (nullable = unlimited), `is_custom`, `is_active`, `sort_order`, `timestamps`
- **Actions:** Factory; seed plans — Starter (200 BDT, 3 clients, 5 projects), Pro, Business, Custom (`is_custom = true`)

### Task 1.24 — Subscription status and interval enums

- **Est.:** XS
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

### Task 2.1 — TenantContext service

- **Est.:** S
- **Depends on:** 1.3
- **Files:** `app/Services/TenantContext.php`, unit test
- **Methods:** `setFreelancerId()`, `freelancerId()`, `hasFreelancer()`

### Task 2.2 — BelongsToFreelancer trait

- **Est.:** S
- **Depends on:** 2.1
- **Apply to:** `Client`, `Project`, `ClientInvoice`
- **Actions:** Global scope + auto-set `freelancer_id` on create

### Task 2.3 — BelongsToTenantViaProject trait

- **Est.:** S
- **Depends on:** 2.1, 1.15
- **Apply to:** `Task`, `TimeLog`, `ClientInvoiceItem`, `ClientInvoicePayment` (scoped via project/invoice chain — not `ClientInvoice`, which uses `BelongsToFreelancer`)
- **Actions:** Scope queries via `whereHas` chain to `freelancer_id`

### Task 2.4 — EnsureFreelancerContext middleware

- **Est.:** M
- **Depends on:** 2.1, 1.6
- **Actions:** Resolve from `X-Freelancer-Id` header, membership fallback, super-admin `?freelancer_id=` override

### Task 2.5 — FreelancerPolicy

- **Est.:** S
- **Depends on:** 1.6

### Task 2.6 — ClientPolicy

- **Est.:** S
- **Depends on:** 1.9

### Task 2.7 — ProjectPolicy

- **Est.:** S
- **Depends on:** 1.12

### Task 2.8 — TaskPolicy

- **Est.:** S
- **Depends on:** 1.15, 2.7
- **Actions:** Validate project belongs to active tenant on all actions

### Task 2.9 — TimeLogPolicy

- **Est.:** S
- **Depends on:** 1.17, 2.8
- **Actions:** Member can log time on own tenant's tasks; can only edit own time logs

### Task 2.10 — ClientInvoicePolicy

- **Est.:** S
- **Depends on:** 1.20, 2.7
- **Actions:** Owner/admin full CRUD; member read-only; validate project tenant on create; block writes when subscription is read-only

### Task 2.11 — Service layer boundaries (no circular deps)

- **Est.:** S
- **Depends on:** 2.1
- **Files:** `app/Services/` namespace layout per [architecture-review.md §3](./architecture-review.md#3-circular-dependencies)
- **Actions:**
  - `Tenancy/` — onboarding, tenant context consumers
  - `Billing/Client/` — `ClientInvoiceService` (totals, numbering, bill time logs)
  - `Billing/Platform/` — `SubscriptionService`, `PlanLimitService`
  - Delivery controllers must not import Platform Billing services
  - Document billing insert order: invoice → items → link `time_logs.client_invoice_item_id`
- **Done when:** No service imports form a cycle; invoice math lives only in `ClientInvoiceService`

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

---

## Phase 3 — Super-admin freelancer onboarding

### Task 3.1 — Freelancer API resource

- **Est.:** XS
- **Depends on:** 1.3

### Task 3.2 — Admin route group

- **Est.:** XS
- **Actions:** `Route::prefix('admin')->middleware(['auth:sanctum', 'can:super-admin'])`

### Task 3.3 — List freelancers (GET /admin/freelancers)

- **Est.:** S

### Task 3.4 — Show freelancer (GET /admin/freelancers/{id})

- **Est.:** S
- **Include:** owner, member count, subscription status summary

### Task 3.5 — FreelancerOnboardingService

- **Est.:** M
- **Actions:** Transaction — Freelancer + User + FreelancerMembership (owner) + Subscription (trialing, default plan)

### Task 3.6 — Create freelancer (POST /admin/freelancers)

- **Est.:** M
- **Payload:** `workspace_name`, `owner_name`, `owner_email`, optional `plan_id`, optional `trial_days`

### Task 3.7 — Update freelancer status (PATCH /admin/freelancers/{id})

- **Est.:** S

### Task 3.8 — Freelancer invite notification

- **Est.:** M

### Task 3.9 — Resend invite (POST /admin/freelancers/{id}/resend-invite)

- **Est.:** S

### Task 3.10 — Admin activity log (super-admin audit trail)

- **Est.:** S
- **Depends on:** 3.2
- **Schema:** `admin_activity_logs` — `id`, `admin_user_id` (FK), `action` (string), `target_type`, `target_id`, `metadata` (json, nullable), `ip_address` (nullable), `timestamps`
- **Actions:**
  - Log when super-admin uses `?freelancer_id=` override, assigns custom plan, suspends workspace
  - `AdminActivityLogger` service — call from admin controllers only
  - No UI required in MVP — queryable for support/debug
- **Done when:** Override request creates log row; test asserts log on admin freelancer show with override

---

## Phase 4 — Client management

### Task 4.1 — Client API resource

- **Est.:** XS

### Task 4.2 — Tenant-scoped route group

- **Est.:** XS
- **Middleware:** `auth:sanctum`, `freelancer.context`

### Task 4.3 — Create client (POST /clients)

- **Est.:** S
- **Actions:** Enforce `PlanLimitService` max_clients before create

### Task 4.4 — List clients (GET /clients)

- **Est.:** S
- **Actions:** Cursor pagination via Task 2.12; optional `?status=active`

### Task 4.5 — Show client (GET /clients/{id})

- **Est.:** S

### Task 4.6 — Update client (PATCH /clients/{id})

- **Est.:** S

### Task 4.7 — Archive client (DELETE /clients/{id})

- **Est.:** S
- **Actions:** Soft-delete or status `Archived`; projects remain

---

## Phase 5 — Project management (client-wise)

### Task 5.1 — Project API resource

- **Est.:** XS
- **Fields:** `client_id`, `name`, `hourly_rate`, `currency`, `status`, `deadline`

### Task 5.2 — Create project (POST /clients/{client}/projects)

- **Est.:** M
- **Payload:** `name`, `hourly_rate` (required), `currency` (optional, default BDT), `deadline`, `status`
- **Actions:** Enforce `PlanLimitService` max_projects before create

### Task 5.3 — List client projects (GET /clients/{client}/projects)

- **Est.:** S
- **Actions:** Cursor pagination (Task 2.12)

### Task 5.4 — List all tenant projects (GET /projects)

- **Est.:** S
- **Actions:** Cursor pagination (Task 2.12)

### Task 5.5 — Show project (GET /projects/{id})

- **Est.:** S

### Task 5.6 — Update project (PATCH /projects/{id})

- **Est.:** S
- **Fields:** `name`, `hourly_rate`, `status`, `deadline`

### Task 5.7 — Soft-delete project (DELETE /projects/{id})

- **Est.:** S

---

## Phase 6 — Task management

### Task 6.1 — Task API resource

- **Est.:** XS
- **Fields:** `title`, `status`, `due_date`, `estimated_hours`

### Task 6.2 — Create task (POST /projects/{project}/tasks)

- **Est.:** S

### Task 6.3 — List project tasks (GET /projects/{project}/tasks)

- **Est.:** S
- **Actions:** Cursor pagination (Task 2.12)

### Task 6.4 — Show task (GET /tasks/{id})

- **Est.:** S

### Task 6.5 — Update task (PATCH /tasks/{id})

- **Est.:** S

### Task 6.6 — Soft-delete task (DELETE /tasks/{id})

- **Est.:** S

---

## Phase 7 — Time logging

### Task 7.1 — TimeLog API resource

- **Est.:** XS

### Task 7.2 — Log time (POST /tasks/{task}/time-logs)

- **Est.:** S
- **Payload:** `hours`, `description`, `logged_at`
- **Actions:** Auto-set `user_id` from authenticated user

### Task 7.3 — List task time logs (GET /tasks/{task}/time-logs)

- **Est.:** S
- **Actions:** **Required** cursor pagination (Task 2.12); sort by `logged_at` desc

### Task 7.4 — Update time log (PATCH /time-logs/{id})

- **Est.:** S
- **Rule:** User can only edit own logs (unless admin)

### Task 7.5 — Delete time log (DELETE /time-logs/{id})

- **Est.:** S

### Task 7.6 — Project time summary (GET /projects/{project}/time-summary)

- **Est.:** S
- **Returns:** Total hours logged vs `estimated_hours` sum across tasks
- **Actions:** Use SQL `SUM(hours)` aggregate — **never** load all `time_logs` into memory

---

## Phase 8 — Client invoicing (`ClientInvoice*`)

### Task 8.1 — ClientInvoice & ClientInvoiceItem API resources

- **Est.:** S
- **Include:** invoice_number, subtotal, tax, total, bill_to snapshot, issued_at, due_date

### Task 8.2 — Create client invoice (POST /projects/{project}/client-invoices)

- **Est.:** M
- **Actions:** Create draft; snapshot `bill_to_*` from client; auto `invoice_number`; optional pre-fill from unbilled time logs at `project.hourly_rate`

### Task 8.3 — Add invoice item (POST /client-invoices/{id}/items)

- **Est.:** S
- **Actions:** Recalculate subtotal, tax, total after each item change

### Task 8.4 — List project invoices (GET /projects/{project}/client-invoices)

- **Est.:** S
- **Actions:** Cursor pagination (Task 2.12)

### Task 8.5 — Show client invoice (GET /client-invoices/{id})

- **Est.:** S
- **Include:** items, payments, outstanding balance

### Task 8.6 — Update client invoice (PATCH /client-invoices/{id})

- **Est.:** S
- **Actions:** `draft` → `sent` sets `issued_at`, `sent_at`; update `due_date`, `notes`, tax_rate

### Task 8.7 — Record client payment (POST /client-invoices/{id}/payments)

- **Est.:** S
- **Payload:** `amount`, `payment_method` (`manual`, `bank_transfer`, `cash`, `other`), `reference`, `paid_at`, `notes`
- **Actions:** Manual record only (MVP); auto-mark `paid` when payments ≥ total

### Task 8.8 — Soft-delete client invoice (DELETE /client-invoices/{id})

- **Est.:** S
- **Rule:** Only `draft` invoices deletable

### Task 8.9 — Mark overdue job

- **Est.:** S
- **Files:** `app/Jobs/MarkOverdueClientInvoices.php`
- **Actions:** Daily — `sent` past `due_date` → `overdue`

---

## Phase 9 — Auth and /me enhancements

### Task 9.1 — FreelancerMembershipResource

- **Est.:** XS

### Task 9.2 — Enhance GET /me

- **Est.:** M
- **Include:** user, freelancer memberships, active freelancer, subscription status summary

### Task 9.3 — Workspace switch via header

- **Est.:** S
- **Document:** `X-Freelancer-Id` header

---

## Phase 10 — Seed data and dev ergonomics

### Task 10.1 — Update seeders

- **Est.:** M
- **Create:** super-admin, one freelancer workspace, 2 clients, 3 projects (with hourly_rate), tasks, time logs, sample ClientInvoice, BDT plans (Starter 200/3/5), trialing subscription

### Task 10.2 — Dev credentials docs

- **Est.:** XS
- **Files:** `docs/multi-tenant/README.md`

---

## Phase 11 — Tenant isolation tests (critical)

### Task 11.1 — Client isolation tests

- **Est.:** S

### Task 11.2 — Project isolation tests

- **Est.:** S

### Task 11.3 — Task and time log isolation tests

- **Est.:** S

### Task 11.4 — ClientInvoice isolation tests

- **Est.:** S

### Task 11.5 — Super-admin tenant override tests

- **Est.:** S

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

### Task 15.1 — Install Laravel Cashier (Stripe)

- **Est.:** M
- **Actions:** `composer require laravel/cashier`; publish migrations; add Stripe keys to `.env`
- **Done when:** Cashier configured; Stripe test mode works

### Task 15.2 — Sync Plan model with Stripe products

- **Est.:** M
- **Actions:** Add `stripe_price_monthly_id`, `stripe_price_yearly_id` to `plans`; artisan command or seeder to create Stripe products/prices
- **Done when:** Each plan has Stripe price IDs for both intervals

### Task 15.3 — Super-admin plan CRUD (GET/POST/PATCH /admin/plans)

- **Est.:** M
- **Who:** Super-admin only

### Task 15.4 — SubscriptionService

- **Est.:** M
- **Methods:**
  - `startTrial(Freelancer, Plan, int $days)`
  - `createCheckoutSession(Freelancer, Plan, BillingInterval)`
  - `swapPlan(Freelancer, Plan, BillingInterval)`
  - `cancel(Freelancer)`
  - `syncFromStripeWebhook(payload)`

### Task 15.5 — GET /subscription (current plan & status)

- **Est.:** S
- **Who:** Freelancer owner
- **Returns:** plan name, status, interval, trial/period dates, days remaining

### Task 15.6 — POST /subscription/checkout

- **Est.:** M
- **Payload:** `plan_id`, `billing_interval` (`monthly` | `yearly`)
- **Returns:** Stripe Checkout URL

### Task 15.7 — POST /subscription/swap

- **Est.:** M
- **Actions:** Change plan or switch monthly ↔ yearly (Stripe proration)

### Task 15.8 — POST /subscription/cancel

- **Est.:** S
- **Actions:** Cancel at period end; set `canceled_at`

### Task 15.9 — Stripe webhook handler (POST /webhooks/stripe)

- **Est.:** M
- **Events:** `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`
- **Actions:** Update `Subscription` status; create `SubscriptionCharge` records; transition to `read_only` on failure

### Task 15.10 — EnsureWritableSubscription middleware

- **Est.:** M
- **Actions:**
  - **Always allow** GET/read routes
  - **Block writes** when status is `read_only`, `canceled` (post-period), or trial expired
  - **Always allow** subscription checkout/swap/cancel routes and super-admin
- **Apply to:** Tenant write route group (after `freelancer.context`)

### Task 15.11 — Plan limit enforcement (PlanLimitService)

- **Est.:** M
- **Actions:**
  - Read `max_clients`, `max_projects`, `max_team_members` from plan; `null` = unlimited (custom plans)
  - Wrap check + create in `DB::transaction()` with `Subscription::lockForUpdate()` on freelancer's subscription row — prevents race at limit boundary
  - Example: Starter (200 BDT) — 3 clients, 5 projects → 422 with upgrade message on exceed

### Task 15.14 — Admin assign custom plan (PATCH /admin/freelancers/{id}/subscription)

- **Est.:** M
- **Actions:** Super-admin assigns `is_custom` plan; set `provider = manual`; unlimited limits; skip Stripe checkout

### Task 15.12 — Subscription notification emails

- **Est.:** S
- **Triggers:** Trial ending (3 days), payment failed, subscription canceled, renewal receipt

### Task 15.13 — Subscription feature tests

- **Est.:** M
- **Cases:**
  - Trial freelancer can write
  - Expired trial → read-only (GET ok, POST 403)
  - Active subscription allows writes
  - Plan limit blocks 4th client on Starter
  - Custom plan with null limits allows unlimited
  - Webhook updates status and creates SubscriptionCharge (mock Stripe)

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
| 17.4 | Async report generation (export CSV/PDF via queue + email link) |
| 17.5 | Per-tenant rate limiting on write APIs |
| 17.6 | Optional denormalized `freelancer_id` on `time_logs` if tenant-wide log reports need it |

---

## Suggested PR grouping

| PR | Tasks | Title |
|----|-------|-------|
| 1 | 1.1 – 1.6 | Freelancer workspace tables and models |
| 2 | 1.7 – 1.12 | Client and project tables and models |
| 3 | 1.13 – 1.17 | Task and time log tables and models |
| 4 | 1.18 – 1.22 | ClientInvoice, ClientInvoiceItem, ClientInvoicePayment models |
| 5 | 1.23 – 1.28 | Plan, Subscription, SubscriptionCharge + indexes |
| 6 | 2.1 – 2.14 | Tenant context, scoping, policies, API conventions, Redis cache |
| 7 | 3.1 – 3.10 | Super-admin onboarding + admin activity log |
| 8 | 4.1 – 4.7 | Client CRUD API |
| 9 | 5.1 – 5.7 | Project CRUD API |
| 10 | 6.1 – 6.6 | Task CRUD API |
| 11 | 7.1 – 7.6 | Time logging API |
| 12 | 8.1 – 8.9 | ClientInvoice API (manual ClientInvoicePayment) |
| 13 | 9.1 – 9.3 | Enhanced /me |
| 14 | 10.1 – 10.2 | Seed data and dev docs |
| 15 | 11.1 – 11.5 | Tenant isolation tests |
| 16 | 15.1 – 15.13 | Platform subscriptions (Stripe) |
| 17+ | 12 – 14, 16 | Role cleanup, team, portal, growth |

---

## Task checklist

```
Phase 0
[ ] 0.1  Verify local environment

Phase 1 — Database
[ ] 1.1  Freelancer status enum
[ ] 1.2  Freelancers migration
[ ] 1.3  Freelancer model
[ ] 1.4  Membership role enum
[ ] 1.5  Memberships migration
[ ] 1.6  FreelancerMembership model
[ ] 1.7  Client status enum
[ ] 1.8  Clients migration
[ ] 1.9  Client model
[ ] 1.10 Project status enum (active/on_hold/completed)
[ ] 1.11 Projects migration (hourly_rate, deadline, softDeletes)
[ ] 1.12 Project model
[ ] 1.13 Task status enum
[ ] 1.14 Tasks migration (softDeletes)
[ ] 1.15 Task model
[ ] 1.16 Time logs migration
[ ] 1.17 TimeLog model
[ ] 1.18 ClientInvoice status enum
[ ] 1.19 client_invoices migration (identity fields, softDeletes)
[ ] 1.20 ClientInvoice model
[ ] 1.21 client_invoice_items migration
[ ] 1.22 ClientInvoiceItem + ClientInvoicePayment models
[ ] 1.23 Plan model (limits, is_custom, BDT)
[ ] 1.24 Subscription enums (incl. ReadOnly)
[ ] 1.25 Subscriptions migration
[ ] 1.26 Subscription model
[ ] 1.27 SubscriptionCharge model
[ ] 1.28 Performance indexes migration

Phase 2 — Tenant isolation
[ ] 2.1  TenantContext service
[ ] 2.2  BelongsToFreelancer trait
[ ] 2.3  BelongsToTenantViaProject trait
[ ] 2.4  EnsureFreelancerContext middleware
[ ] 2.5  FreelancerPolicy
[ ] 2.6  ClientPolicy
[ ] 2.7  ProjectPolicy
[ ] 2.8  TaskPolicy
[ ] 2.9  TimeLogPolicy
[ ] 2.10 ClientInvoicePolicy
[ ] 2.11 Service layer boundaries
[ ] 2.12 Tenant API conventions (cursor pagination)
[ ] 2.13 Redis cache configuration
[ ] 2.14 Cache services (plans, subscription, memberships)

Phase 3 — Super-admin onboarding
[ ] 3.1 – 3.10 (includes trial subscription + admin activity log)

Phase 4 — Clients
[ ] 4.1 – 4.7

Phase 5 — Projects
[ ] 5.1 – 5.7

Phase 6 — Tasks
[ ] 6.1 – 6.6

Phase 7 — Time logs
[ ] 7.1 – 7.6

Phase 8 — Client invoicing
[ ] 8.1 – 8.9

Phase 9 — Auth /me
[ ] 9.1 – 9.3

Phase 10 — Seed data
[ ] 10.1 – 10.2

Phase 11 — Isolation tests
[ ] 11.1 – 11.5

Phase 12 — Role cleanup (optional)
[ ] 12.1 – 12.3

Phase 13 — Freelancer team
[ ] 13.1 – 13.4

Phase 14 — Client portal
[ ] 14.1 – 14.5

Phase 15 — Platform subscriptions
[ ] 15.1  Install Cashier / Stripe
[ ] 15.2  Sync plans with Stripe prices
[ ] 15.3  Admin plan CRUD
[ ] 15.4  SubscriptionService
[ ] 15.5  GET /subscription
[ ] 15.6  POST /subscription/checkout
[ ] 15.7  POST /subscription/swap
[ ] 15.8  POST /subscription/cancel
[ ] 15.9  Stripe webhooks
[ ] 15.10 EnsureWritableSubscription middleware (read-only mode)
[ ] 15.11 Plan limit enforcement (clients, projects, team)
[ ] 15.12 Subscription emails
[ ] 15.13 Subscription tests
[ ] 15.14 Admin assign custom plan

Phase 16 — Future
[ ] 16.x Platform growth
```
