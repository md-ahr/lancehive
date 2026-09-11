# Stack & Environment

Tech stack, pinned versions, config files, and `.env` structure.

---

## Tech stack

| Layer | Technology | Notes |
|-------|------------|-------|
| Language | PHP 8.5 | Sail runtime (`compose.yaml` → `sail-8.5/app`) |
| Framework | Laravel 13 | Anonymous migrations, `casts()`, attribute-based models |
| Database | PostgreSQL 18 | Sail service `pgsql`; native partial indexes, JSON metadata |
| Cache | Redis | Plans, subscription, memberships — not tenant data lists |
| Queue | Database | `QUEUE_CONNECTION=database` default |
| Auth | Laravel Sanctum | Bearer tokens from `POST /login` |
| API docs | dedoc/scramble | UI `/docs/api` · spec `/docs/api.json` |
| Testing | Pest 4 + PHPUnit 12 | Feature + unit; sqlite in-memory for tests |
| Formatting | Laravel Pint | Run on dirty PHP before finishing |
| Local dev | Laravel Sail | Docker Compose — **all commands via `vendor/bin/sail`** |
| Frontend build | Vite 8 + Tailwind 4 | Minimal — API-first MVP |

---

## Dependency versions

Composer constraints (`composer.json`) vs typical locked versions:

| Package | Constraint | Role |
|---------|------------|------|
| `php` | `^8.3` | Runtime (Sail uses 8.5 image) |
| `laravel/framework` | `^13.17` | Core framework |
| `laravel/sanctum` | `^4.0` | API token auth |
| `dedoc/scramble` | `^0.13.43` | OpenAPI generation |
| `pestphp/pest` | `^4.7` | Test runner |
| `laravel/sail` | `^1.67` | Docker dev environment |
| `laravel/pint` | `^1.27` | Code formatter |
| `laravel/boost` | `^2.8` | MCP dev tools (dev only) |
| `resend/resend-php` | `^1.14` | Resend mail transport (staging/production) |

Check live versions before relying on package APIs:

```bash
vendor/bin/sail composer show --direct
```

npm (`package.json`):

| Package | Constraint | Role |
|---------|------------|------|
| `vite` | `^8.0.0` | Asset bundler |
| `tailwindcss` | `^4.0.0` | CSS |
| `laravel-vite-plugin` | `^3.1` | Laravel integration |

Do **not** add packages without explicit approval.

---

## Sail services

From `compose.yaml`:

| Service | Image | Host port (default) |
|---------|-------|---------------------|
| `laravel.test` | `sail-8.5/app` | `80` (APP_PORT) |
| `pgsql` | `postgres:18-alpine` | `5432` (FORWARD_DB_PORT) |
| `redis` | `redis:alpine` | `6379` (FORWARD_REDIS_PORT) |
| `mailpit` | `axllent/mailpit` | `1025` SMTP, `8025` dashboard |

Start / stop:

```bash
vendor/bin/sail up -d
vendor/bin/sail stop
```

---

## Config files

| File | Purpose |
|------|---------|
| `config/api.php` | `route_version`, `prefix` (`api/v1`), `features_routes` |
| `config/scramble.php` | OpenAPI path, security, UI, `API_VERSION` info field |
| `config/database.php` | PostgreSQL connection (Sail) |
| `config/cache.php` | Redis store |
| `config/sanctum.php` | Token abilities, expiration |
| `config/security.php` | Login lockout thresholds |
| `config/cors.php` | SPA CORS policy |
| `config/auth.php` | Guards, providers |
| `config/mail.php` | Mailers (`smtp`, `resend`, `log`, …); default from `MAIL_MAILER` |
| `config/services.php` | Third-party keys (`RESEND_API_KEY`, Stripe, AWS) |
| `bootstrap/app.php` | Routing, JSON exception rendering, `ApiException` handler |
| `phpunit.xml` | Test env overrides (sqlite, array cache, sync queue) |
| `compose.yaml` | Sail Docker services |

Read config values:

```bash
vendor/bin/sail artisan config:show api.prefix
vendor/bin/sail artisan config:show database.default
```

---

## `.env` structure

Copy from `.env.example` on first setup (`composer run setup` handles this). Defaults target **Laravel Sail** (PostgreSQL + Redis); sqlite is commented as a non-Sail alternative.

### Application

| Variable | Example | Purpose |
|----------|---------|---------|
| `APP_NAME` | `Lancehive` | App name; used in mail, Vite, Scramble UI title |
| `APP_ENV` | `local` | Environment (`local`, `production`, `testing` forced in PHPUnit) |
| `APP_KEY` | *(generated)* | Encryption key — run `vendor/bin/sail artisan key:generate` |
| `APP_DEBUG` | `true` | Debug mode; enables Scramble dev tools by default |
| `APP_URL` | `http://localhost` | Base URL (Sail port 80) |
| `APP_LOCALE` | `en` | Default locale |
| `FRONTEND_URL` | `http://localhost:5173` | SPA origin (future client apps) |
| `SANCTUM_TOKEN_EXPIRATION` | `43200` | Bearer token lifetime in minutes (30 days) |
| `LOGIN_MAX_ATTEMPTS` | `10` | Failed logins before account lockout |
| `LOGIN_LOCKOUT_MINUTES` | `15` | Account lock duration |
| `CORS_ALLOWED_ORIGINS` | *(falls back to `FRONTEND_URL`)* | Comma-separated allowed SPA origins |
| `TRUSTED_PROXIES` | `*` (behind nginx) | Proxy IPs/CIDRs for `X-Forwarded-*` |
| `SESSION_SECURE_COOKIE` | `false` locally | Set `true` in production (HTTPS) |

### API versioning (two distinct vars)

| Variable | Example | Purpose |
|----------|---------|---------|
| `API_ROUTE_VERSION` | `v1` | **URL segment** → `/api/v1/*`; drives `config/api.php` and route file path |
| `API_VERSION` | `1.0.0` | **OpenAPI info version** (semver in `/docs/api.json`) — not the URL prefix |

### Database

**Sail (recommended local):**

```dotenv
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

**Bare metal / CI without Sail:** adjust host to `127.0.0.1`.

**PHPUnit (automatic — do not set in `.env`):** `phpunit.xml` forces sqlite `:memory:`.

### Cache & Redis

```dotenv
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis          # Use "redis" inside Sail; "127.0.0.1" outside Docker
REDIS_PASSWORD=null
REDIS_PORT=6379
```

| Environment | `CACHE_STORE` | Why |
|-------------|---------------|-----|
| Sail / production | `redis` | Recommended — avoids PostgreSQL cache table contention |
| PHPUnit | `array` | Forced in `phpunit.xml` — no Redis needed in tests |
| Avoid in production | `database` | Competes with tenant data on PostgreSQL |

### Queue & session

```dotenv
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

Tests override: `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`.

#### Queue architecture

| Use case | Mechanism | Location |
|----------|-----------|----------|
| Transactional email | Queued notifications (`ShouldQueue` + `via: ['mail']`) | `app/Features/*/Notifications/` |
| Report CSV export | `GenerateReportExportJob::dispatch()` after creating a pending `ReportExport` | `ReportExportController`, `AdminReportExportController` |
| Daily overdue invoices | Scheduled job → queue (`MarkOverdueClientInvoicesJob`) | `bootstrap/app.php` |
| Trial-ending reminders | Scheduled Artisan command (sends queued notifications inline) | `subscriptions:notify-trial-ending` |

**Drivers:** `database` locally (Sail) · `redis` or a managed queue in production. `config/queue.php` sets `after_commit => true` on `database` and `redis` so queued work runs only after open DB transactions commit.

**Workers (required outside tests):**

- Sail: `queue` service in `compose.yaml` runs `php artisan queue:work` automatically.
- Production: managed queue (Laravel Cloud) or a dedicated `queue:work` / Horizon process.
- Scheduler: cron `* * * * * php artisan schedule:run` — required for daily jobs/commands.

Without a running worker, queued notifications and `GenerateReportExportJob` stay in the `jobs` table; Mailpit/Resend will not receive mail.

### Mail

Transactional email uses Laravel Notifications (`via: ['mail']`). All notification classes are queued — a running queue worker is required for delivery in non-test environments.

#### Local Sail → Mailpit

Capture outbound mail without a third-party account:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

FORWARD_MAILPIT_PORT=1025
FORWARD_MAILPIT_DASHBOARD_PORT=8025
```

Dashboard: `http://localhost:8025`

#### Staging / production → Resend

Laravel 13’s built-in Resend driver (`resend/resend-php`). Keep Mailpit locally; set Resend only on deployed environments:

```dotenv
MAIL_MAILER=resend
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
RESEND_API_KEY=re_xxxxxxxx
```

**Before go-live:**

1. Verify your sending domain in the [Resend dashboard](https://resend.com/domains).
2. Set `MAIL_FROM_ADDRESS` to an address on that verified domain.
3. Store `RESEND_API_KEY` as a deployment secret (never commit real keys).
4. Ensure a queue worker processes jobs (`queue:work` or managed queue).

Config wiring (already in repo): `config/mail.php` mailer `resend` + `config/services.php` → `services.resend.key`.

#### Tests

PHPUnit forces `MAIL_MAILER=array` — no real mail is sent during tests.

### Logging

```dotenv
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
```

### Vite

```dotenv
VITE_APP_NAME="${APP_NAME}"
```

Build: `vendor/bin/sail npm run build` · dev: `vendor/bin/sail npm run dev`

### AWS (optional — file storage)

```dotenv
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
FILESYSTEM_DISK=local
```

### Sail port overrides (optional)

```dotenv
APP_PORT=80
VITE_PORT=5173
FORWARD_DB_PORT=5432
FORWARD_REDIS_PORT=6379
WWWUSER=1000
WWWGROUP=1000
```

---

## Environment matrix

| Setting | `.env` (Sail) | `phpunit.xml` | Production guidance |
|---------|---------------|---------------|---------------------|
| `APP_ENV` | `local` | `testing` | `production` |
| `APP_DEBUG` | `true` | — | `false` |
| `DB_CONNECTION` | `pgsql` | `sqlite` | `pgsql` |
| `DB_HOST` | `pgsql` | — | managed host |
| `CACHE_STORE` | `redis` | `array` | `redis` |
| `REDIS_HOST` | `redis` | — | managed Redis |
| `QUEUE_CONNECTION` | `database` | `sync` | `redis` or managed queue |
| `MAIL_MAILER` | `smtp` (Mailpit) | `array` | `resend` |
| `RESEND_API_KEY` | *(empty)* | — | deployment secret |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | — | verified Resend sender domain |
| `APP_DEBUG` | `true` | — | **`false`** |
| `LOG_LEVEL` | `debug` | — | **`error`** or `warning` |
| `SANCTUM_TOKEN_EXPIRATION` | `43200` | — | Review TTL; shorter for stricter prod |
| `SESSION_SECURE_COOKIE` | `false` | — | **`true`** |
| `RATE_LIMIT_STORE` | `redis` | `array` | **`redis`** |

---

## Pre-launch security checklist

Before deploying to a VPS (Hostinger, AWS, DigitalOcean, etc.):

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `APP_KEY` generated and stored as deployment secret
- [ ] `LOG_LEVEL=error` (or `warning`) — not `debug`
- [ ] `SANCTUM_TOKEN_EXPIRATION` set (default 43200 minutes)
- [ ] `SESSION_SECURE_COOKIE=true` when serving HTTPS
- [ ] `TRUSTED_PROXIES` matches your nginx / load balancer
- [ ] `CORS_ALLOWED_ORIGINS` lists only your SPA domain(s)
- [ ] `CACHE_STORE=redis` and `RATE_LIMIT_STORE=redis`
- [ ] Stripe / Resend keys in deployment secrets — not in Git
- [ ] `/docs/api` blocked in production (`viewApiDocs` gate)
- [ ] TLS termination + HTTP→HTTPS redirect at nginx (see [`deployment/README.md`](../deployment/README.md))
- [ ] Queue worker and scheduler cron running
- [ ] Run `composer audit --locked` before release

---

## First-time setup

```bash
cp .env.example .env
# Edit DB_* and REDIS_HOST for Sail (see above)
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
vendor/bin/sail composer install
vendor/bin/sail npm install
vendor/bin/sail npm run build
```

Or use the Composer shortcut:

```bash
composer run setup
```

---

## Secrets

- Never commit `.env`
- `.env.example` documents keys with safe placeholders only
- Stripe and other third-party keys will be added per integration task — not in MVP `.env.example` yet

---

## Useful URLs (Sail defaults)

| URL | Purpose |
|-----|---------|
| `http://localhost` | Application |
| `http://localhost/docs/api` | Scramble API docs UI |
| `http://localhost/docs/api.json` | OpenAPI JSON |
| `http://localhost:8025` | Mailpit inbox |
| `http://localhost:5173` | Vite dev server |

Resolve exact URLs in agent responses via Boost `get-absolute-url` when sharing links with the user.
