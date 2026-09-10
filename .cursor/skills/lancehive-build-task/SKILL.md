---
name: lancehive-build-task
description: Use when implementing a task from docs/multi-tenant/implementation-tasks.md. Triggers on "task X.Y", phase work, onboarding APIs, tenant isolation, billing, or any ordered build step. Enforces done-when criteria and test delivery per task.
---

# LanceHive Build Task

## Before coding

1. Open **only** the target task section in `docs/multi-tenant/implementation-tasks.md` (do not read the full file). Paths are under **`/api/v1`**.
2. Note **Depends on**, **Files**, and **Done when**.
3. Confirm dependencies exist in codebase; if not, implement dependency tasks first.
4. Activate `lancehive-testing` and `lancehive-api-docs` skills.

## Workflow

1. `vendor/bin/sail artisan make:` for new files (model, migration, controller, resource, request). Tests: `make:test --pest`.
2. Place domain code in `app/Features/{Feature}/` — **never** `app/Features/.../V1/` while only v1 exists.
3. New API route file → `routes/features/v1/{feature}.php`, required in `routes/api.php` via `config('api.features_routes')`.
4. Controller/Request/Resource in `app/Features/{Feature}/Http/` + `#[Group]` + Scramble doc test (see `lancehive-api-docs`).
5. Tests in `tests/Feature/{Feature}/` and `tests/Unit/{Feature}/` — not under `V1/`.
6. `vendor/bin/sail artisan test --compact {test-path}` including `tests/Feature/Api/DocumentationTest.php` when routes changed.
7. `vendor/bin/sail bin pint --dirty --format agent`

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
