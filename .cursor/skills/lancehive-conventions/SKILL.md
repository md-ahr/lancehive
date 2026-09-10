---
name: lancehive-conventions
description: Use when writing PHP code, choosing file placement, naming classes, configuring env, or onboarding to LanceHive patterns. Triggers on coding conventions, folder rules, naming, tech stack, dependencies, or .env setup.
---

# LanceHive Conventions

## Sources of truth

| Topic | Doc |
|-------|-----|
| Coding style & layers | `docs/development/coding-conventions.md` |
| Naming & folders | `docs/development/naming-and-folders.md` |
| Stack, deps, config, `.env` | `docs/development/stack-and-environment.md` |
| Feature layout (detail) | `docs/project-structure/feature-based-architecture.md` |
| API HTTP rules | `docs/api/conventions.md` |
| Security & auth | `docs/development/security-and-auth.md` |
| Error handling | `docs/development/error-handling.md` |
| Testing strategy | `docs/development/testing-strategy.md` |

Activate `lancehive-architecture` for module boundaries; `lancehive-guardrails` for auth/errors/tests; `lancehive-testing` for Pest tests.

## Quick rules (agents)

### Commands — always Sail

```bash
vendor/bin/sail artisan …
vendor/bin/sail artisan test --compact tests/Feature/{Feature}/{Name}Test.php
vendor/bin/sail bin pint --dirty --format agent
```

### PHP style

- `declare(strict_types=1);` on every PHP file
- `final` classes unless extension is intended
- Explicit return types and parameter types on all methods
- Constructor property promotion for DI
- `casts()` method (not `$casts` property) · `#[Fillable]` / `#[Hidden]` attributes (not `$fillable` / `$hidden`)
- Enum keys: TitleCase · backed enums in `app/Features/{Feature}/Enums/`
- PHPDoc array shapes where helpful; no inline comments for obvious code

### Layer flow (downward only)

```
HTTP (Controller → Form Request → API Resource)
  → Application (Action / Service)
    → Domain (Model, Enum, Policy)
      → Infrastructure (Job, Listener)
```

- Controllers: validate via Form Request, delegate to Action/Service, return API Resource
- **No** business rules, `DB::table()`, plan limits, or invoice math in controllers
- Core never imports Features; Features may import Core

### Naming

| Artifact | Pattern | Example |
|----------|---------|---------|
| Controller | `{Entity}Controller` | `ClientController` |
| Form request | `{Verb}{Entity}Request` | `StoreClientRequest` |
| API resource | `{Entity}Resource` | `ClientResource` |
| Action | `{Verb}{Entity}Action` | `CreateClientAction` |
| Service | `{Entity}Service` | `ClientInvoiceService` |
| Policy | `{Entity}Policy` | `ProjectPolicy` |
| Enum | `{Entity}{Attribute}` | `ProjectStatus` |
| Model | Singular PascalCase | `TimeLog` |
| Factory | `database/factories/{Feature}/{Model}Factory.php` | `Delivery/ClientFactory.php` |
| Feature test | `tests/Feature/{Feature}/{Endpoint}Test.php` | `Delivery/StoreClientTest.php` |

### Folder rules

| Versioned | Not versioned |
|-----------|---------------|
| `routes/features/v1/*.php` | `app/Features/{Feature}/` |
| URL `/api/v1` | `database/`, factories, seeders |
| Scramble `api_path` | `tests/Feature/{Feature}/` — use `$this->apiUrl()` |

**Do not** add `V1/` under `app/Features/` while only one API version exists.

New endpoint: `routes/features/v1/{feature}.php` → controller in `app/Features/{Feature}/Http/Controllers/`.

### Module imports (mandatory)

| Feature | May import | Must NOT |
|---------|------------|----------|
| Auth | Core | other Features |
| Tenancy | Auth, Core | Delivery, Billing |
| Delivery | Tenancy, Core | ClientBilling, PlatformBilling |
| ClientBilling | Delivery, Tenancy, Core | PlatformBilling |
| PlatformBilling | Tenancy, Core | ClientBilling, Delivery |

### Stack (pinned)

PHP 8.5 (Sail runtime) · Laravel 13 · PostgreSQL 18 · Redis · Pest 4 · Sanctum · Scramble

Run `vendor/bin/sail composer show --direct` before assuming package APIs.

### Environment essentials

| Var | Purpose |
|-----|---------|
| `API_ROUTE_VERSION=v1` | URL segment → `/api/v1` |
| `API_VERSION=1.0.0` | OpenAPI info version (semver) |
| `CACHE_STORE=redis` | App cache (Sail/production); tests force `array` |
| `DB_CONNECTION=pgsql` | Sail default; tests use sqlite `:memory:` |
| `REDIS_HOST=redis` | Inside Sail containers |

Full reference: `docs/development/stack-and-environment.md`

## Do not

- Run PHP/Artisan/Composer without `vendor/bin/sail`
- Hardcode `/api/v1` in tests — use `$this->apiUrl()`
- Add dependencies without user approval
- Create new top-level `app/` folders without approval
- Return raw `response()->json()` from new API endpoints
- Use `CACHE_STORE=database` in production (PostgreSQL competes with tenant data)
