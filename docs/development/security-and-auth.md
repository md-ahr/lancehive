# Security & Auth Spec

Agent reference for authentication, authorization, and tenant isolation. **Read this before touching auth, policies, middleware, or tenant-scoped routes.**

Related: [api/conventions.md](../api/conventions.md) · [acceptance-criteria.md](../multi-tenant/acceptance-criteria.md) · [architecture-overview.md](../multi-tenant/architecture-overview.md) § Role model

---

## Request flow (tenant routes)

```
HTTP request
  → auth:sanctum                    # Bearer token → User
  → EnsureFreelancerContext         # X-Freelancer-Id / membership fallback / admin override
  → writable.subscription           # Block writes when subscription is read-only (write routes only)
  → Policy authorize                # Membership + role matrix
  → Eloquent global scope           # BelongsToFreelancer / BelongsToTenantViaProject
  → Action / Service                # Business rules, plan limits
  → API Resource response
```

Admin routes (`/admin/*`, `GET /users`) skip tenant middleware and use `can:super-admin` instead.

---

## Authentication (Sanctum)

| Concern | Rule |
|---------|------|
| Mechanism | Laravel Sanctum bearer token |
| Header | `Authorization: Bearer {token}` |
| Token source | `POST /login` → `token` field in `LoginResource` |
| Public routes | `login`, `forgot-password`, `reset-password`, `webhooks/stripe` |
| Protected routes | `auth:sanctum` middleware |
| Logout | `POST /logout` — revokes current token |

### Login & password reset

| Route | Throttle | Notes |
|-------|----------|-------|
| `POST /login` | `login` (5/min per IP) | Wrong credentials → `422` on `errors.email` (no user enumeration) |
| `POST /forgot-password` | `password-reset` (3/min per IP) | Always returns success message |
| `POST /reset-password` | `password-reset` (3/min per IP) | Invalid token → `422` |

Password rules: min 8 chars; production adds mixed case, symbols, uncompromised check (`AppServiceProvider`).

### Tests

Use `Sanctum::actingAs($user)` or `$this->actingAs($user, 'sanctum')`. Never bypass auth in production code.

---

## Role model (two levels)

### Platform role — `users.role` (`UserRole` enum)

| Value | Who | API access |
|-------|-----|------------|
| `super_admin` | Platform operator | `/admin/*`, `GET /users`, tenant override on admin routes |
| `user` | Everyone else | Tenant routes when `freelancer_memberships` exist; `/portal/*` when `client_memberships` exist (Phase 14) |
| `freelancer` | **Deprecated** — migrated to `user` | — |
| `client` | **Deprecated** — migrated to `user` | — |

Gate: `can:super-admin` → `User::isSuperAdmin()`.

**Current state (Phase 12):** everyone except super-admin has `users.role = user`; workspace and portal permissions come from pivot tables. `User::isFreelancer()` / `isClient()` check memberships, not the `users.role` column.

### Workspace role — `freelancer_memberships.role` (`FreelancerMembershipRole`)

| Value | Tenant permissions |
|-------|-------------------|
| `owner` | Full CRUD + subscription management |
| `admin` | Full CRUD on clients, projects, tasks, invoices |
| `member` | Tasks + time logs (own logs editable); read clients/projects/invoices |

### Client portal role — `client_memberships.role` (`ClientMembershipRole`, Phase 14)

| Value | Portal permissions |
|-------|-------------------|
| `primary` | Read project/task visibility per product spec |
| `viewer` | Read-only |

---

## Authorization matrix (MVP)

Policies live in `app/Features/{Feature}/Policies/`. Call from Form Request `authorize()`, Action, or controller — **never inline role checks in controllers**.

| Resource | view | create | update | delete | Member restrictions |
|----------|------|--------|--------|--------|-------------------|
| Freelancer workspace | member | — | owner/admin | — | Non-member → deny |
| Client | member | owner/admin | owner/admin | owner/admin (archive) | Member read-only |
| Project | member | owner/admin | owner/admin | owner/admin (soft) | Member read-only; client must belong to tenant |
| Task | member | member | member | member (soft) | Project must belong to tenant |
| TimeLog | member | member | **own only** | **own only** | Admin/owner edits any |
| ClientInvoice | member | owner/admin | owner/admin | owner/admin (draft) | Member read-only |
| Workspace settings | member | — | owner/admin | — | Member read-only; resolved from `TenantContext` |
| User settings | self | — | self | — | `/me/settings` only |
| Platform settings | super-admin | — | super-admin | — | `/admin/settings` only |

### Settings authorization

| Endpoint | Middleware | Read | Write |
|----------|------------|------|-------|
| `GET/PATCH /me/settings` | `auth:sanctum` | Self | Self |
| `GET /workspace/settings` | `auth:sanctum`, `freelancer.context` | Any member | — |
| `PATCH /workspace/settings` | above + `writable.subscription` | — | Owner/admin (`canManageClientsAndProjects()`) |
| `GET/PATCH /admin/settings` | `auth:sanctum`, `can:super-admin` | Super-admin | Super-admin |

**Notification preference gates** (user scope):

| Key | Honored when |
|-----|--------------|
| `subscription_alerts` | User is workspace owner (same rule as `/subscription/*`) |
| `workspace_invites` | Always (all roles) |
| `invoice_activity` | User is owner or admin |

Policy: `WorkspaceSettingsPolicy` in `app/Features/Settings/Policies/`.

### Subscription read-only (`writable.subscription`)

When subscription status is `read_only` (trial expired, unpaid):

- All tenant **writes** → `403 workspace_read_only`
- Reads continue to work
- **Exception:** subscription checkout/swap/cancel routes remain writable

Enforced in middleware, **not** in policies.

---

## Tenant context

| Mechanism | Detail |
|-----------|--------|
| Header | `X-Freelancer-Id: {id}` on all tenant-scoped routes |
| Middleware | `EnsureFreelancerContext` sets `TenantContext` |
| Single membership | Auto-select workspace when user has exactly one membership |
| Multi membership | Header required — missing → `403` or `422` |
| Non-member ID | `403 forbidden` |
| Super-admin override | `?freelancer_id=` on **admin routes only** — log to `admin_activity_logs` |
| Query scoping | `BelongsToFreelancer` on `Client`, `Project`, `ClientInvoice` |
| Indirect scoping | `BelongsToTenantViaProject` on `Task`, `TimeLog`, `ClientInvoiceItem`, `ClientInvoicePayment` |

### Cross-tenant access (critical)

| Scenario | HTTP | `code` |
|----------|------|--------|
| Resource ID belongs to another workspace | **404** | `not_found` |
| User not a workspace member | 403 | `forbidden` |
| Member lacks policy permission (same tenant) | 403 | `forbidden` |

**Never return 403 for cross-tenant resource IDs** — that leaks existence. Policy denies → controller/middleware maps to `404 not_found`.

---

## Route groups

| Group | Middleware | Who |
|-------|------------|-----|
| Public auth | `throttle:*` | Anyone |
| Authenticated | `auth:sanctum` | Any logged-in user (`/me`, `/me/settings`, `/logout`) |
| Tenant read | `auth:sanctum`, `freelancer.context` | Workspace member |
| Tenant write | above + `writable.subscription` | Writable subscription |
| Admin | `auth:sanctum`, `can:super-admin` | Super-admin |
| Subscription self-serve | `auth:sanctum`, `freelancer.context` | Owner — writes allowed even when read-only |
| Webhooks | Signature verification (Stripe) | Payment provider |

---

## What agents must never do

### Authentication & secrets

- **Never** commit `.env`, API keys, Stripe secrets, or tokens to the repo
- **Never** log passwords, bearer tokens, or reset tokens
- **Never** disable `auth:sanctum` on tenant or admin routes to "make tests pass"
- **Never** add auth bypass middleware or `if (app()->environment('local'))` shortcuts in production paths
- **Never** return different error messages for "user not found" vs "wrong password" on login

### Tenant isolation

- **Never** query tenant models without global scope in tenant controllers (`Client::withoutGlobalScopes()`, raw `DB::table()`)
- **Never** accept `freelancer_id` from request body — set from `TenantContext` only
- **Never** trust parent IDs (`client_id`, `project_id`, `task_id`) without verifying they belong to the active tenant
- **Never** return `403` when the real issue is cross-tenant resource access — use `404 not_found`
- **Never** use `withoutGlobalScopes()` in tenant controllers — admin/onboarding code only
- **Never** skip `X-Freelancer-Id` in tenant feature tests

### Authorization

- **Never** check `$user->role` or membership role inline in controllers — use Policies
- **Never** expose super-admin capabilities to regular users (e.g. allow `?freelancer_id=` on tenant routes)
- **Never** let members mutate resources their policy forbids (invoices, other users' time logs)

### Data exposure

- **Never** include hidden fields (`password`, `remember_token`) in API Resources
- **Never** return stack traces or SQL errors to API clients in production
- **Never** enumerate whether an email exists via forgot-password response

### Architecture boundaries

- **Never** import across forbidden feature boundaries (see `lancehive-conventions` module import table)
- **Never** put authorization logic only in the frontend — API must enforce every rule
- **Never** duplicate plan-limit or invoice math outside `PlanLimitService` / `ClientInvoiceService`

---

## Implementation checklist (per endpoint)

Before marking an endpoint done:

- [ ] Route has correct middleware group (see table above)
- [ ] `#[HeaderParameter('X-Freelancer-Id')]` on tenant controllers (Scramble)
- [ ] Form Request validates input; `authorize()` calls Policy
- [ ] Action/Service throws `ApiException` for business denials
- [ ] Global scope applied on queried models
- [ ] Feature test: 401 unauthenticated, 403 non-member, 404 cross-tenant, policy denial
- [ ] Isolation test when resource is tenant-scoped (Phase 11+)
- [ ] Endpoint errors documented in `docs/api/endpoints/{group}.md`

---

## Cursor skills & rules

Entry skill: **`lancehive-guardrails`**. Also: `lancehive-api-contract` (endpoint errors), `lancehive-testing` (isolation tests). Rules: `tenancy-isolation.mdc`, `api-conventions.mdc`, `testing-required.mdc`, `feature-architecture.mdc`.
