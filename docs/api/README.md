# LanceHive API Contract

Human-readable API contract for the LanceHive MVP (`/api/v1`). This is the **design source**; Scramble OpenAPI at `/docs/api.json` is the **runtime truth** generated from code.

## Quick links

| Doc | Purpose |
|-----|---------|
| [conventions.md](./conventions.md) | Base URL, auth, headers, pagination, data types |
| [errors.md](./errors.md) | Error catalog with HTTP status + machine-readable `code` |
| [schemas/](./schemas/) | Reusable JSON shapes (Resources) |
| [endpoints/](./endpoints/) | Per-route request/response/error specs |

**Agent guardrails** (deeper than this index): [security-and-auth.md](../development/security-and-auth.md) · [error-handling.md](../development/error-handling.md) · [testing-strategy.md](../development/testing-strategy.md) — entry skill: `lancehive-guardrails`

## Live documentation

- **UI:** `/docs/api` (Stoplight Elements)
- **OpenAPI JSON:** `/docs/api.json`
- **Config:** `config/scramble.php`, `config/api.php`

## Authentication flow

1. `POST /api/v1/login` with `email` + `password` → receive `token`
2. Send `Authorization: Bearer {token}` on protected routes
3. For tenant-scoped routes, also send `X-Freelancer-Id: {freelancer_id}`

## Endpoint index (MVP)

| Group | File | Routes | Status |
|-------|------|--------|--------|
| Authentication | [endpoints/auth.md](./endpoints/auth.md) | login, logout, forgot/reset password | Implemented |
| Users | [endpoints/users.md](./endpoints/users.md) | `GET /users` | Implemented |
| Me | [endpoints/me.md](./endpoints/me.md) | `GET /me` | Implemented |
| Settings | [endpoints/settings.md](./endpoints/settings.md) | `/me/settings`, `/workspace/settings`, `/admin/settings` | Implemented |
| Members | [endpoints/members.md](./endpoints/members.md) | `/members/*` | Implemented |
| Client Members | [endpoints/client-members.md](./endpoints/client-members.md) | `/clients/{client}/members` | Implemented |
| Client Portal | [endpoints/portal.md](./endpoints/portal.md) | `/portal/*` | Implemented |
| Admin — Freelancers | [endpoints/admin-freelancers.md](./endpoints/admin-freelancers.md) | `/admin/freelancers/*` | Implemented |
| Admin — Plans | [endpoints/admin-plans.md](./endpoints/admin-plans.md) | `/admin/plans/*` | Implemented |
| Clients | [endpoints/clients.md](./endpoints/clients.md) | `/clients/*` | Implemented |
| Projects | [endpoints/projects.md](./endpoints/projects.md) | `/projects/*`, nested under clients | Implemented (time-summary in Phase 7.6) |
| Tasks | [endpoints/tasks.md](./endpoints/tasks.md) | `/tasks/*`, nested under projects | Implemented |
| Time logs | [endpoints/time-logs.md](./endpoints/time-logs.md) | `/time-logs/*`, nested under tasks | Implemented |
| Client invoices | [endpoints/client-invoices.md](./endpoints/client-invoices.md) | `/client-invoices/*` | Implemented |
| Subscription | [endpoints/subscription.md](./endpoints/subscription.md) | `/subscription/*` | Implemented |
| Webhooks | [endpoints/webhooks.md](./endpoints/webhooks.md) | `POST /webhooks/stripe` | Implemented |

## Coverage (MVP cross-check)

54 endpoints documented across 13 endpoint files — all routes from `implementation-tasks.md` Phases 3–15 plus Phase 18 settings (planned).

| File | Endpoints |
|------|-----------|
| auth.md | 4 |
| users.md | 1 |
| me.md | 1 |
| settings.md | 6 |
| admin-freelancers.md | 6 |
| admin-plans.md | 3 |
| clients.md | 5 |
| projects.md | 7 |
| tasks.md | 5 |
| time-logs.md | 4 |
| client-invoices.md | 7 |
| subscription.md | 4 |
| webhooks.md | 1 |

## Post-MVP (not documented here)

- Phase 16+ growth features (except Phase 18 settings — documented above as planned)

## Keeping contract and code aligned

When implementing an endpoint:

1. Read the matching `endpoints/*.md` block
2. Activate `lancehive-guardrails` if endpoint touches auth, tenant scope, or new error codes
3. Create Form Request + API Resource per `lancehive-api-docs` skill
4. Assert path in `tests/Feature/Api/DocumentationTest.php`
5. Feature tests assert JSON keys match `schemas/*.md` — plan matrix per `lancehive-testing` / [testing-strategy.md](../development/testing-strategy.md)

Skills: `lancehive-api-contract` (markdown workflow) · `lancehive-api-docs` (Scramble) · Rules: `api-scramble-docs.mdc`, `api-conventions.mdc`, `error-handling.mdc`
