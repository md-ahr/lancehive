# Error Catalog

LanceHive API errors use Laravel's JSON envelope with an optional machine-readable `code` for non-validation failures.

Strategy: [development/error-handling.md](../development/error-handling.md) · Skill: `lancehive-guardrails` · Rule: `error-handling.mdc`

## Envelopes

### Validation error (422)

No `code` field — Laravel default:

```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

### Business / auth error

Includes `code`:

```json
{
  "message": "Starter plan allows 3 clients. Upgrade to add more.",
  "code": "plan_limit_exceeded"
}
```

## Error codes

| HTTP | `code` | When |
|------|--------|------|
| 401 | `unauthenticated` | Missing, invalid, or expired bearer token |
| 403 | `forbidden` | Policy denial (wrong role or insufficient permission) |
| 403 | `workspace_read_only` | Write blocked — subscription lapsed or trial expired |
| 403 | `super_admin_required` | Non-admin accessed `/admin/*` or `GET /users` |
| 404 | `not_found` | Resource missing or cross-tenant access |
| 410 | `export_expired` | Report export past `expires_at` |
| 422 | `validation_failed` | Form Request validation (implicit — no `code` in body) |
| 422 | `plan_limit_exceeded` | `PlanLimitService` — max clients, projects, or team members |
| 422 | `invoice_not_editable` | Mutating a non-draft client invoice |
| 429 | `too_many_requests` | Rate-limited routes (auth, API, writes, reports, admin, subscription, webhooks) |

## Implementation

- Enum: `App\Core\Http\Enums\ApiErrorCode`
- Exception: `App\Core\Http\Exceptions\ApiException`
- Thrown from services/middleware; rendered in `bootstrap/app.php`

## Per-endpoint errors

Each endpoint block in [endpoints/](./endpoints/) lists applicable errors. Common patterns:

| Scenario | HTTP | `code` |
|----------|------|--------|
| Unauthenticated request to protected route | 401 | `unauthenticated` |
| User not a workspace member | 403 | `forbidden` |
| Member lacks policy permission | 403 | `forbidden` |
| Write while subscription read-only | 403 | `workspace_read_only` |
| Resource ID from another tenant | 404 | `not_found` |
| Invalid request body/query | 422 | (validation envelope) |
| Exceed plan limit on create | 422 | `plan_limit_exceeded` |

## Rate limiting

Two-layer strategy (see `deployment/nginx/lancehive.conf` + `config/rate-limiting.php`):

| Layer | Technology | Purpose |
|-------|------------|---------|
| 1 — Edge | Nginx `limit_req` per IP | Blocks DDoS/scrapers before Laravel (~50 req/s default) |
| 2 — Application | Redis throttle counters | Granular limits per bearer token, user, workspace, and plan tier |

All API routes use Redis-backed throttle counters (`RATE_LIMIT_STORE`, default `redis`). Limits are per minute unless noted.

| Scope | Throttle name | Default | Key |
|-------|---------------|---------|-----|
| All `/api/v1/*` | `api` | 120/min | Sanctum token ID, user ID, or IP |
| `POST /login` | `login` | 5/min | IP |
| `POST /forgot-password`, `POST /reset-password` | `password-reset` | 3/min | IP |
| Tenant write routes | `tenant-writes` | 60/min × plan multiplier | Token/user + `X-Freelancer-Id` |
| Report run / export create | `reports` | 10/min | Token/user + `X-Freelancer-Id` |
| `/admin/*` | `admin` | 120/min | Token ID or user ID |
| Subscription checkout/swap/cancel | `subscription` | 10/min | Token/user + `X-Freelancer-Id` |
| `POST /webhooks/stripe` | `webhooks` | 120/min | IP |

Response: **429** with `code: too_many_requests` and `Retry-After` header.

Configure via `config/rate-limiting.php` or env vars (`RATE_LIMIT_API`, `RATE_LIMIT_PLAN_PRO_MULTIPLIER`, etc.).
