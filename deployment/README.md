# Production Deployment (VPS)

Reference for self-hosted deployment on Hostinger VPS, AWS EC2, DigitalOcean Droplet, or similar. Laravel Cloud handles TLS and edge automatically — use this guide for nginx + PHP-FPM stacks.

Related: [`nginx/lancehive.conf`](nginx/lancehive.conf) · [`docs/development/stack-and-environment.md`](../docs/development/stack-and-environment.md) § Pre-launch security checklist

---

## Stack

| Layer | Recommendation |
|-------|----------------|
| OS | Ubuntu 22.04+ LTS |
| Web | nginx → PHP-FPM 8.3+ (or `php artisan serve` behind nginx for small setups) |
| Database | PostgreSQL 18 (managed or self-hosted) |
| Cache / throttles | Redis |
| Queue | `php artisan queue:work` systemd service |
| Scheduler | Cron: `* * * * * cd /path && php artisan schedule:run` |

---

## Environment

Copy production values from [`docs/development/stack-and-environment.md`](../docs/development/stack-and-environment.md) § Pre-launch security checklist.

Minimum production `.env` differences from local:

```dotenv
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
SESSION_SECURE_COOKIE=true
SANCTUM_TOKEN_EXPIRATION=43200
TRUSTED_PROXIES=*
CORS_ALLOWED_ORIGINS=https://app.yourdomain.com
CACHE_STORE=redis
RATE_LIMIT_STORE=redis
```

Never commit real `.env` files. Store secrets in the host's secret manager or restricted file permissions (`chmod 600 .env`).

---

## nginx

1. Copy [`nginx/lancehive.conf`](nginx/lancehive.conf) to `/etc/nginx/conf.d/lancehive.conf`.
2. Add TLS certificates (Let's Encrypt / certbot recommended).
3. Enable HTTP → HTTPS redirect on port 80.
4. Set `real_ip` / `set_real_ip_from` when behind a load balancer so Laravel rate limits see client IPs.
5. Tune edge rate limits to match `NGINX_RATE_LIMIT_RPS` / `NGINX_RATE_LIMIT_BURST` in `.env`.

Application-layer security headers are set by `SecurityHeaders` middleware; nginx may duplicate HSTS for defense in depth.

---

## Post-deploy verification

```bash
curl -I https://yourdomain.com/api/v1/up
composer audit --locked
php artisan config:cache
php artisan route:cache
```

Confirm:

- `/docs/api` returns 403 in production
- Login rate limit returns 429 after threshold
- HTTPS responses include security headers

---

## Dependency audit

Run weekly or before each release:

```bash
composer audit --locked
```

Fix high-severity CVEs before deploying.
