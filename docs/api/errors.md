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
| 422 | `validation_failed` | Form Request validation (implicit — no `code` in body) |
| 422 | `plan_limit_exceeded` | `PlanLimitService` — max clients, projects, or team members |
| 422 | `invoice_not_editable` | Mutating a non-draft client invoice |
| 429 | `too_many_requests` | Rate-limited routes (`login`, `forgot-password`, `reset-password`) |

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

| Route | Throttle name | Response |
|-------|---------------|----------|
| `POST /login` | `login` | 429 `too_many_requests` |
| `POST /forgot-password` | `password-reset` | 429 `too_many_requests` |
| `POST /reset-password` | `password-reset` | 429 `too_many_requests` |
