# Development Guide

Agent and developer reference for how LanceHive code is written, organized, and run locally.

| Document | Purpose |
|----------|---------|
| [Coding Conventions](./coding-conventions.md) | PHP style, layers, controller/action patterns, formatting |
| [Naming & Folders](./naming-and-folders.md) | Class naming, directory layout, module imports, tests |
| [Stack & Environment](./stack-and-environment.md) | Versions, dependencies, config files, `.env` structure |
| [Security & Auth](./security-and-auth.md) | Sanctum flow, roles, policies, tenant isolation, agent never-do list |
| [Error Handling](./error-handling.md) | Envelopes, `ApiException`, logging, where to throw |
| [Testing Strategy](./testing-strategy.md) | Unit vs feature vs journey, test matrix, isolation plan |

## Related docs

| Need | File |
|------|------|
| Feature module layout (full) | [feature-based-architecture.md](../project-structure/feature-based-architecture.md) |
| Domain & billing names | [architecture-overview.md](../multi-tenant/architecture-overview.md) |
| API HTTP contract | [api/conventions.md](../api/conventions.md) |
| API contract index | [api/README.md](../api/README.md) |
| Build order | [implementation-tasks.md](../multi-tenant/implementation-tasks.md) |

## Agent rules (`.ai/rules/`)

Committed path-scoped rules for Boost-compatible agents. Read [`.ai/rules/index.md`](../../.ai/rules/index.md) before coding — maps globs to architecture, API, testing, and workflow notes. Cursor path rules in `.cursor/rules/` apply in parallel.

## Principles

1. **Thin HTTP, fat Application** — controllers delegate; business rules live in Actions/Services.
2. **Feature slices** — code lives under `app/Features/{Feature}/`, not a flat `app/Services/` dump.
3. **Explicit shared code** — cross-cutting infrastructure in `app/Core/` only.
4. **Version routes, not domain** — `/api/v1` in route files; models and services are not duplicated per version.
5. **Sail for everything** — PHP, Artisan, Composer, Node, and tests run inside Docker via `vendor/bin/sail`.
6. **Test what changed** — narrow Pest runs after each edit; Pint on dirty PHP before finishing.

## Cursor skills

| Skill | When |
|-------|------|
| `lancehive-build-task` | Implementing a task from `implementation-tasks.md` |
| `lancehive-guardrails` | Auth, policies, errors, or test planning — routes to the three guardrail specs above |
| `lancehive-conventions` | Writing PHP, file placement, naming, `.env` |
| `lancehive-architecture` | Module boundaries, tenancy, billing layers |
| `lancehive-testing` | Writing or reviewing Pest tests |
| `lancehive-api-docs` | Scramble OpenAPI — controllers, Form Requests, Resources |
| `lancehive-api-contract` | Markdown contract in `docs/api/` — endpoints, schemas, errors |

## Cursor rules (`.cursor/rules/`)

Rules auto-apply when editing matching paths. Always-on: `00-lancehive-core.mdc`, `01-ai-token-guard.mdc`, `02-agent-verification.mdc`.

| Rule | Scope |
|------|-------|
| `01-ai-token-guard.mdc` | Always — minimum context, doc reads, search budget |
| `02-agent-verification.mdc` | Always — narrow Sail tests after code changes; no full suite unless asked |
| `feature-architecture.mdc` | `app/Features/**` — layers, module imports |
| `api-conventions.mdc` | HTTP layer, routes, pagination, errors summary |
| `api-scramble-docs.mdc` | Controllers, routes, API doc tests |
| `api-versioning.mdc` | Route versioning, `$this->apiUrl()` |
| `tenancy-isolation.mdc` | Tenancy, middleware, policies, isolation tests |
| `error-handling.mdc` | `ApiException`, actions, services |
| `testing-required.mdc` | `tests/**` — unit + feature + doc test per task |
| `postgres-laravel13.mdc` | Migrations, PostgreSQL-specific |
