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
| Me | [endpoints/me.md](./endpoints/me.md) | `GET /me` | Partial (enhancement in Phase 9) |
| Admin — Freelancers | [endpoints/admin-freelancers.md](./endpoints/admin-freelancers.md) | `/admin/freelancers/*` | Implemented |
| Admin — Plans | [endpoints/admin-plans.md](./endpoints/admin-plans.md) | `/admin/plans/*` | Planned (Phase 15) |
| Clients | [endpoints/clients.md](./endpoints/clients.md) | `/clients/*` | Planned (Phase 4) |
| Projects | [endpoints/projects.md](./endpoints/projects.md) | `/projects/*`, nested under clients | Planned (Phase 5) |
| Tasks | [endpoints/tasks.md](./endpoints/tasks.md) | `/tasks/*`, nested under projects | Planned (Phase 6) |
| Time logs | [endpoints/time-logs.md](./endpoints/time-logs.md) | `/time-logs/*`, nested under tasks | Planned (Phase 7) |
| Client invoices | [endpoints/client-invoices.md](./endpoints/client-invoices.md) | `/client-invoices/*` | Planned (Phase 8) |
| Subscription | [endpoints/subscription.md](./endpoints/subscription.md) | `/subscription/*` | Planned (Phase 15) |
| Webhooks | [endpoints/webhooks.md](./endpoints/webhooks.md) | `POST /webhooks/stripe` | Planned (Phase 15) |

## Coverage (MVP cross-check)

48 endpoints documented across 12 endpoint files — all routes from `implementation-tasks.md` Phases 3–15 (excluding Phase 13–14 post-MVP).

| File | Endpoints |
|------|-----------|
| auth.md | 4 |
| users.md | 1 |
| me.md | 1 |
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

- Phase 13 — Freelancer team invites (`POST /members`, etc.)
- Phase 14 — Client portal (`/portal/*`)

## Keeping contract and code aligned

When implementing an endpoint:

1. Read the matching `endpoints/*.md` block
2. Activate `lancehive-guardrails` if endpoint touches auth, tenant scope, or new error codes
3. Create Form Request + API Resource per `lancehive-api-docs` skill
4. Assert path in `tests/Feature/Api/DocumentationTest.php`
5. Feature tests assert JSON keys match `schemas/*.md` — plan matrix per `lancehive-testing` / [testing-strategy.md](../development/testing-strategy.md)

Skills: `lancehive-api-contract` (markdown workflow) · `lancehive-api-docs` (Scramble) · Rules: `api-scramble-docs.mdc`, `api-conventions.mdc`, `error-handling.mdc`
