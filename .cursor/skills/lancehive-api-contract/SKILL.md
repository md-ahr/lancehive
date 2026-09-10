---
name: lancehive-api-contract
description: Use when writing or updating API contract docs in docs/api/, defining endpoint request/response shapes, error codes, or keeping markdown specs aligned with Scramble OpenAPI. Triggers on API contract, endpoint spec, error catalog, or schema documentation.
---

# LanceHive API Contract

## Sources of truth

| Layer | Location | Role |
|-------|----------|------|
| Design | `docs/api/` | Human-readable contract — write/update first |
| Runtime | `/docs/api.json` | Scramble OpenAPI — generated from code |
| Domain | `docs/multi-tenant/architecture-overview.md` | Entity columns → Resource fields |
| Tasks | `docs/multi-tenant/implementation-tasks.md` | Endpoint list + payloads |

Activate with `lancehive-api-docs`, `lancehive-guardrails` (errors/auth), and `lancehive-testing` on every contract task.

Error implementation detail: `docs/development/error-handling.md` · Rule: `error-handling.mdc`

## Workflow

### Pass A — Design (markdown)

1. Read target task section in `implementation-tasks.md`
2. Read entity schema in `architecture-overview.md`
3. Write/update `docs/api/schemas/{entity}.md` field list
4. Write/update `docs/api/endpoints/{group}.md` using template below
5. Cross-check errors against `docs/api/errors.md`

### Pass B — Code sync (implementation)

1. Form Request → Scramble infers request schema
2. API Resource → Scramble infers response schema
3. `#[Group]`, `#[HeaderParameter]`, `#[Endpoint]` on controller
4. Business errors → `throw new ApiException(ApiErrorCode::..., $message)`
5. Assert path in `tests/Feature/Api/DocumentationTest.php`
6. Feature test JSON keys match `schemas/*.md`

## Namespace order

Work in this order when documenting full MVP:

1. Auth + Users + Me
2. Admin — Freelancers, Admin — Plans
3. Clients → Projects → Tasks → Time logs
4. Client invoices
5. Subscription + Webhooks

## Endpoint template

```markdown
### POST /clients

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ClientPolicy@create` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | yes | max:255 |

**Response `201`** — `ClientResource` — see [schemas/client.md](../schemas/client.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | Missing/invalid token |
| 403 | forbidden | Not a workspace member |
| 403 | workspace_read_only | Subscription lapsed |
| 422 | validation_failed | Invalid input |
| 422 | plan_limit_exceeded | max_clients reached |
```

## Scramble sync checklist

- [ ] Route in `routes/features/v1/{feature}.php`
- [ ] Controller `#[Group('…', weight: N)]`
- [ ] Form Request for body/query
- [ ] API Resource return type (not raw `response()->json()`)
- [ ] `#[HeaderParameter('X-Freelancer-Id')]` on tenant controllers
- [ ] Path asserted in `DocumentationTest`
- [ ] `vendor/bin/sail artisan test --compact tests/Feature/Api/DocumentationTest.php`

## Error codes

Use `App\Core\Http\Enums\ApiErrorCode` + `App\Core\Http\Exceptions\ApiException`. Full catalog: `docs/api/errors.md`.

Validation errors use Laravel default (no `code`). Business/auth errors include `code`.

## Do not

- Hand-write OpenAPI YAML — Scramble generates from code
- Document Phase 13–14 post-MVP routes in MVP contract
- Return 403 for cross-tenant access — use 404 `not_found`
- Skip `docs/api/` update when changing request/response shapes
