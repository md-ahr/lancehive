# LanceHive

Multi-tenant freelancer workspace API — clients, projects, time tracking, client invoicing, and platform subscriptions. Headless backend consumed by a separate SPA at `/api/v1`.

**Stack:** Laravel 13 · PHP 8.5 · PostgreSQL 18 · Redis · Sanctum · Sail · Pest · Scramble

## Quick start

```bash
composer install
cp .env.example .env   # or: composer run setup
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate:fresh --seed
```

| Surface | URL |
|---------|-----|
| API discovery | `GET /` |
| REST API | `/api/v1/*` |
| OpenAPI UI | `/docs/api` |
| OpenAPI JSON | `/docs/api.json` |
| Health | `/up` |

All PHP, Artisan, Composer, and test commands run through Sail: `vendor/bin/sail artisan …`

After seeding, dev accounts use password `password` — see [multi-tenant README](docs/multi-tenant/README.md#dev-credentials-local-only) for roles and the demo workspace.

## Documentation

| Area | Entry point |
|------|-------------|
| API contract | [docs/api/README.md](docs/api/README.md) |
| User journeys (auth → delivery → billing) | [docs/api/user-journey.md](docs/api/user-journey.md) |
| Domain & tenancy | [docs/multi-tenant/README.md](docs/multi-tenant/README.md) |
| Build backlog | [docs/multi-tenant/implementation-tasks.md](docs/multi-tenant/implementation-tasks.md) |
| Coding conventions & stack | [docs/development/README.md](docs/development/README.md) |
| Feature module layout | [docs/project-structure/README.md](docs/project-structure/README.md) |

## Agentic development

LanceHive is built for AI-assisted development (Cursor, Claude Code, Copilot).

| Resource | Purpose |
|----------|---------|
| [Development guide](docs/development/README.md) | Conventions, security, errors, testing |
| [Implementation tasks](docs/multi-tenant/implementation-tasks.md) | Ordered build backlog + checklist |
| [`.ai/rules/`](.ai/rules/index.md) | Path-scoped rules for agents (read `index.md` first) |
| [`.cursor/skills/`](.cursor/skills/) | LanceHive skills (`lancehive-build-task`, `lancehive-guardrails`, …) |

**Example prompt:** `Implement task 2.1 using lancehive-build-task. Activate lancehive-guardrails.`

[Laravel Boost](https://laravel.com/docs/ai) is installed — MCP via `.mcp.json` (`vendor/bin/sail artisan boost:mcp`).

## Check security

```bash
php artisan truss:doctor
php artisan checkpoint:scan
composer audit
php artisan optimize
```

## License

MIT
