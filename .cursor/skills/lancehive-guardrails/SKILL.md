---
name: lancehive-guardrails
description: Use before auth, policies, middleware, tenant isolation, ApiException, or test planning in LanceHive. Triggers on security, authorization, permissions, error handling, ApiErrorCode, cross-tenant access, isolation tests, or "avoid costly mistakes". Routes agents to the correct guardrail spec — read only what applies.
---

# LanceHive Guardrails

Costly-mistake prevention for security, errors, and tests. **Read the matching spec before coding** — do not load all three unless the task spans them.

## Which spec to read

| Task touches | Read first |
|--------------|------------|
| Login, Sanctum, roles, policies, middleware, `X-Freelancer-Id`, tenant scope, admin routes | `docs/development/security-and-auth.md` |
| `ApiException`, `ApiErrorCode`, validation vs business errors, logging, HTTP status mapping | `docs/development/error-handling.md` |
| Unit/feature/isolation tests, test matrix, what to assert per endpoint | `docs/development/testing-strategy.md` |

**Multi-area tasks** (e.g. new tenant write endpoint): read security → error-handling → testing-strategy in that order, then implement.

Also activate: `lancehive-testing` (tests) · `lancehive-api-contract` (endpoint errors in `docs/api/errors.md`).

---

## Quick rules (memorize)

| Rule | Detail |
|------|--------|
| Cross-tenant ID | **404** `not_found` — never 403 |
| Same-tenant policy deny | **403** `forbidden` |
| Validation | **422** — Laravel envelope, **no** `code` |
| Business deny | `throw new ApiException(ApiErrorCode::…)` |
| Tenant writes | `auth:sanctum` + `freelancer.context` + `writable.subscription` |
| `freelancer_id` | From `TenantContext` only — never request body |
| Authorization | Policies — never inline role checks in controllers |
| Tests per task | Unit + feature + Scramble doc path |
| Isolation | Two workspaces; foreign ID → `assertNotFound()` |

---

## Workflow

### Auth / policy / middleware task

1. Read `docs/development/security-and-auth.md` § relevant section only
2. Confirm middleware group and policy matrix match the endpoint
3. Read `docs/development/error-handling.md` if adding new failure modes
4. Plan tests per `docs/development/testing-strategy.md` § Security test cases
5. Document errors in `docs/api/endpoints/{group}.md` and `docs/api/errors.md` if new `ApiErrorCode`

### Error / exception task

1. Read `docs/development/error-handling.md`
2. Add enum case → default message → catalog → endpoint spec → render test
3. Throw from Action/Service — not controller JSON

### Test-only task

1. Read `docs/development/testing-strategy.md` § Plan before coding
2. Write test matrix (happy, 401, 403, 404, 422 as applicable)
3. Activate `lancehive-testing` for templates and run commands

---

## Pre-merge checklist

- [ ] No item in security spec **"What agents must never do"** violated
- [ ] Cross-tenant returns 404; policy deny returns 403
- [ ] New `ApiErrorCode` cases documented and tested
- [ ] Feature test includes `X-Freelancer-Id` on tenant routes
- [ ] Isolation test for tenant-scoped resources (Phase 11+)
- [ ] Narrow test run passes; Pint on dirty PHP

---

## Cursor rules (auto-apply by path)

| Rule | Scope |
|------|-------|
| `01-ai-token-guard.mdc` | Always — context and read budget |
| `02-agent-verification.mdc` | Always — when to run narrow tests vs skip |
| `tenancy-isolation.mdc` | Tenancy, middleware, policies, isolation tests |
| `error-handling.mdc` | `ApiException`, actions, services |
| `testing-required.mdc` | All tests |
| `api-conventions.mdc` | HTTP layer, routes |
| `api-scramble-docs.mdc` | Controllers, API doc tests |

## Deep reference (on demand)

| Need | File |
|------|------|
| Policy matrix detail | `docs/multi-tenant/acceptance-criteria.md` § Phase 2 |
| Error catalog | `docs/api/errors.md` |
| API HTTP rules | `docs/api/conventions.md` |
| Build task context | `lancehive-build-task` skill |
