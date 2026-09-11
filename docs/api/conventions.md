# API Conventions

Global rules inherited by every endpoint. Individual route specs in [endpoints/](./endpoints/) reference this document.

## Base URL

| Setting | Value |
|---------|-------|
| Prefix | `/api/v1` |
| Config | `config('api.prefix')` |
| Env | `API_ROUTE_VERSION=v1` |

All paths in this documentation are relative to `/api/v1` (e.g. `POST /clients` → `POST /api/v1/clients`).

## Authentication

| Mechanism | Detail |
|-----------|--------|
| Provider | Laravel Sanctum (bearer token) |
| Header | `Authorization: Bearer {token}` |
| Token source | `POST /login` response `token` field |
| Public routes | `login`, `forgot-password`, `reset-password`, `webhooks/stripe` |

## Tenant context

Tenant-scoped routes require the active workspace header:

```
X-Freelancer-Id: {freelancer_id}
```

Resolved by `EnsureFreelancerContext` middleware. Super-admins may use `?freelancer_id=` query override on admin routes (logged to `admin_activity_logs`).

### Workspace switching

| Context | Header required? | Behavior |
|---------|------------------|----------|
| `GET /me` | Optional | When sent, selects `active_freelancer` and `subscription` in the response. Invalid membership → `403 forbidden`. When omitted and the user has exactly one membership, that workspace is auto-selected. |
| Tenant routes (`/clients`, `/projects`, …) | Required when user has multiple memberships | Sets `TenantContext` for the request. Same membership rules as above. |
| Single membership | No | Workspace auto-selected on tenant routes and on `GET /me`. |

Send the same `X-Freelancer-Id` value on every tenant-scoped request after switching workspaces in the client UI.

## Client portal context

Client portal routes require the active client organization header:

```
X-Client-Id: {client_id}
```

Resolved by `EnsureClientContext` middleware.

| Context | Header required? | Behavior |
|---------|------------------|----------|
| `GET /me` | Optional | When sent, selects `active_client` in the response. Invalid membership → `403 forbidden`. When omitted and the user has exactly one client membership, that organization is auto-selected. |
| Portal routes (`/portal/*`) | Required when user has multiple client memberships | Sets `ClientContext` for the request. |
| Single client membership | No | Client organization auto-selected on portal routes and on `GET /me`. |

## Route groups

| Group | Middleware | Writes when subscription lapsed |
|-------|------------|-----------------------------------|
| Public auth | `throttle:login`, `throttle:password-reset` | N/A |
| Authenticated | `auth:sanctum`, `throttle:api` | N/A |
| Tenant read | `auth:sanctum`, `freelancer.context`, `throttle:api` | N/A |
| Tenant write | above + `writable.subscription`, `throttle:tenant-writes` | Blocked when lapsed |
| Reports (run/export create) | `throttle:reports` (plus tenant/admin middleware) | Owner/admin only |
| Admin | `auth:sanctum`, `can:super-admin`, `throttle:admin` | Always allowed |
| Subscription mutations | `throttle:subscription` on checkout/swap/cancel | Always allowed |
| Stripe webhooks | signature verification, `throttle:webhooks` | N/A |

## Pagination (list endpoints)

All list endpoints use **cursor pagination**. See [schemas/pagination.md](./schemas/pagination.md).

| Query param | Default | Max | Invalid value |
|-------------|---------|-----|---------------|
| `per_page` | 25 | 100 | `422 validation_failed` if > 100 |
| `cursor` | — | — | Opaque cursor from previous response |

## Data types

| Type | JSON representation | Example |
|------|---------------------|---------|
| ID | integer | `42` |
| Enum | snake_case string | `"in_progress"`, `"active"` |
| Date | ISO 8601 date | `"2026-03-10"` |
| DateTime | ISO 8601 with timezone | `"2026-03-10T12:00:00+00:00"` |
| Money | decimal string (2 places) | `"1200.00"` |
| Boolean | `true` / `false` | `"is_custom": false` |
| Nullable | `null` | `"contact_email": null` |

## Soft deletes

`DELETE` on projects, tasks, and draft client invoices sets `deleted_at`. List endpoints exclude soft-deleted records by default.

## Cross-tenant access

When a resource belongs to another workspace, return **`404 not_found`** — never `403` — to avoid leaking resource existence.

## Success response patterns

| Pattern | HTTP | Envelope |
|---------|------|----------|
| Single resource | `200` or `201` | Resource object directly or wrapped (see endpoint spec) |
| List (cursor) | `200` | `{ data: [...], links: {...}, meta: {...} }` |
| Message only | `200` | `{ message: "..." }` |
| Delete | `204` or `200` | Empty or `{ message: "..." }` per endpoint spec |

## Scramble groups (OpenAPI ordering)

| Group | Weight |
|-------|--------|
| Authentication | 0 |
| Users | 1 |
| Admin | 10 |
| Tenancy | 20 |
| Clients / Projects / Tasks / Time Logs | 30–40 |
| Client Invoices | 50 |
| Subscriptions | 60 |

## Agent reference

| Topic | Doc / skill |
|-------|-------------|
| Security & auth detail | `docs/development/security-and-auth.md` · `lancehive-guardrails` |
| Error envelopes & `ApiException` | `docs/development/error-handling.md` · `error-handling.mdc` |
| Endpoint markdown specs | `docs/api/endpoints/` · `lancehive-api-contract` |
| Scramble / OpenAPI | `lancehive-api-docs` · `api-scramble-docs.mdc` |
