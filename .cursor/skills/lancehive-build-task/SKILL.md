---
name: lancehive-build-task
description: Use when implementing a task from docs/multi-tenant/implementation-tasks.md. Triggers on "task X.Y", phase work, onboarding APIs, tenant isolation, billing, or any ordered build step. Enforces done-when criteria and test delivery per task.
---

# LanceHive Build Task

## Before coding

1. Open **only** the target task section in `docs/multi-tenant/implementation-tasks.md` (do not read the full file). Read the phase **Acceptance pattern** on the same screen. Read **Task sequencing** only if phase gates or cross-phase stubs apply. Paths are under **`/api/v1`**.
2. Note **Depends on**, **Done when**, **Tests**, and **Edge cases**. For Phases **2**, **11**, or **15**, open the linked section in `docs/multi-tenant/acceptance-criteria.md` for matrices — do not read other phases there.
3. Confirm dependencies exist in codebase; if not, implement dependency tasks first.
4. Activate `lancehive-guardrails` when task touches auth, policies, middleware, errors, or isolation; then `lancehive-testing`, `lancehive-api-docs`, and `lancehive-api-contract` for new endpoints.

## Workflow

1. `vendor/bin/sail artisan make:` for new files (model, migration, controller, resource, request). Tests: `make:test --pest`.
2. Place domain code in `app/Features/{Feature}/` — **never** `app/Features/.../V1/` while only v1 exists.
3. New API route file → `routes/features/v1/{feature}.php`, required in `routes/api.php` via `config('api.features_routes')`.
4. Controller/Request/Resource in `app/Features/{Feature}/Http/` + `#[Group]` + Scramble doc test (see `lancehive-api-docs`).
5. Tests in `tests/Feature/{Feature}/` and `tests/Unit/{Feature}/` — not under `V1/`.
6. `vendor/bin/sail artisan test --compact {test-path}` — narrow file only (`02-agent-verification.mdc`); include `DocumentationTest` when routes changed.
7. `vendor/bin/sail bin pint --dirty --format agent`
8. **Update checklist** — in `docs/multi-tenant/implementation-tasks.md` § Task checklist, change the completed task(s) from `[ ]` to `[x]` and refresh **Last verified** date. Mark a phase header with ✅ only when every task in that phase is `[x]`.

## Phase map (quick reference)

| Phase | Focus |
|-------|-------|
| 1 | Models, enums, migrations, factories |
| 2 | TenantContext, traits, middleware, policies, cursor pagination, Redis cache |
| 3 | Super-admin freelancer onboarding |
| 4–8 | Client → Project → Task → TimeLog → ClientInvoice APIs |
| 9 | `/me` enhancements |
| 10 | Seeders |
| 11 | Tenant isolation tests (critical) |
| 15 | Stripe subscriptions, read-only mode, plan limits |

## PR grouping

Follow **Suggested PR grouping** table at bottom of `implementation-tasks.md` when user asks for PR scope.

## Deep reference (on demand only)

- Entity schemas → `docs/multi-tenant/architecture-overview.md`
- Indexes → `docs/multi-tenant/database-erd.md` §8
- Service boundaries → `docs/multi-tenant/architecture-review.md` §3
- Versioning & folders → `docs/project-structure/feature-based-architecture.md` §8.1
- Security / errors / tests → `docs/development/security-and-auth.md`, `error-handling.md`, `testing-strategy.md` via `lancehive-guardrails`
