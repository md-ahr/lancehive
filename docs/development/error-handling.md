# Error Handling Strategy

How LanceHive catches, logs, and returns errors to API clients. **Read this before adding exceptions, middleware rejections, or validation rules.**

Related: [api/errors.md](../api/errors.md) (catalog) · [coding-conventions.md](./coding-conventions.md) § API errors

Entry skill: **`lancehive-guardrails`** · Rule: `error-handling.mdc`

---

## Principles

1. **Predictable envelopes** — clients can branch on HTTP status + optional `code`
2. **No information leakage** — cross-tenant and missing resources → `404`; no stack traces in API responses
3. **Validation vs business** — input shape failures use Laravel validation; domain rules use `ApiException`
4. **Throw, don't return** — services/actions throw; controllers do not craft error JSON manually
5. **Log internally, sanitize externally** — full detail in server logs; minimal detail in JSON body

---

## Error layers

```
Throwable
  ├── ValidationException          → 422, Laravel validation envelope (no code)
  ├── AuthenticationException      → 401, unauthenticated
  ├── AuthorizationException       → 403, forbidden (policy / gate)
  ├── ModelNotFoundException       → 404, not_found (when mapped)
  ├── ApiException                 → status from ApiErrorCode + code field
  ├── ThrottleRequestsException    → 429, too_many_requests
  └── Unhandled Exception          → 500, generic message (production)
```

Configured in `bootstrap/app.php`:

- JSON rendering enabled for `api/*` and `expectsJson()` requests
- `ApiException` rendered via `$exception->render($request)`

---

## Response envelopes

### Validation error (422) — no `code`

Laravel default. Used for Form Request failures and invalid query params.

```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

**When:** missing fields, invalid formats, `per_page` > 100, wrong credentials on login (`errors.email`).

**Do not** add a `code` field to validation responses.

### Business / auth error — includes `code`

```json
{
  "message": "Starter plan allows 3 clients. Upgrade to add more.",
  "code": "plan_limit_exceeded"
}
```

**When:** plan limits, read-only workspace, invoice state, explicit business denials.

**Implementation:**

```php
throw new ApiException(
    ApiErrorCode::PlanLimitExceeded,
    'Starter plan allows 3 clients. Upgrade to add more.',
);
```

Classes: `App\Core\Http\Enums\ApiErrorCode` · `App\Core\Http\Exceptions\ApiException`.

### Message-only success

```json
{ "message": "Logged out successfully." }
```

Not an error — used for auth flows returning `MessageResource`.

---

## Error code catalog

Full table: [api/errors.md](../api/errors.md). Summary:

| HTTP | `code` | Source | When |
|------|--------|--------|------|
| 401 | `unauthenticated` | `auth:sanctum` | Missing/invalid/expired token |
| 403 | `forbidden` | Policy, gate, middleware | Wrong role, not a member, policy deny (same tenant) |
| 403 | `workspace_read_only` | `EnsureWritableSubscription` | Write while subscription lapsed |
| 403 | `super_admin_required` | `can:super-admin` | Non-admin on admin routes |
| 404 | `not_found` | Explicit throw or model binding | Missing resource or **cross-tenant** ID |
| 422 | *(none)* | Form Request | Validation failure |
| 422 | `plan_limit_exceeded` | `PlanLimitService` | max_clients / projects / team |
| 422 | `invoice_not_editable` | `ClientInvoiceService` | Mutate non-draft invoice |
| 429 | `too_many_requests` | Rate limiter | login, password reset |

### Adding a new code

1. Add case to `ApiErrorCode` with correct `httpStatus()`
2. Add default message in `ApiException::defaultMessage()`
3. Document in `docs/api/errors.md`
4. List on affected endpoint specs in `docs/api/endpoints/`
5. Unit test render shape in `tests/Feature/Api/ErrorResponseTest.php` or dedicated test
6. Feature test the HTTP path that triggers it

**Do not** invent ad-hoc `code` strings outside the enum.

---

## Where to throw what

| Layer | Responsibility |
|-------|----------------|
| **Form Request** | Input validation → automatic `422` |
| **Form Request `authorize()`** | Policy check → `403 forbidden` via Laravel |
| **Middleware** | Context missing, read-only, rate limit → `ApiException` or framework exception |
| **Policy** | Return `false` → `403`; cross-tenant → prefer `404` at controller/query layer |
| **Action / Service** | Business rules → `throw new ApiException(...)` |
| **Controller** | No error crafting — delegate and return Resource |

### Cross-tenant pattern

```php
// In Action or controller — after scoped query
$client = Client::query()->find($id);

if ($client === null) {
    throw new ApiException(ApiErrorCode::NotFound);
}
```

Use scoped queries so foreign IDs naturally return `null` → `404`.

### Plan limits

Only in `PlanLimitService` — never duplicated in controllers:

```php
$this->planLimits->assertCanAddClient($freelancerId);
// throws ApiException(ApiErrorCode::PlanLimitExceeded, '...')
```

---

## Logging

| Event | Log? | Level | Notes |
|-------|------|-------|-------|
| Validation failure | No | — | Expected client error |
| 401 / 403 policy deny | Optional | `info` | Include user ID, route — not token |
| 404 cross-tenant | Optional | `warning` | Useful for abuse detection; no PII in message |
| `ApiException` business | Optional | `info` | Plan limit, read-only — expected |
| Rate limit hit | Yes | `warning` | IP + route |
| Unhandled exception | Yes | `error` | Full trace in log; generic JSON to client |
| Webhook signature failure | Yes | `warning` | Never log raw payload secrets |
| Super-admin tenant override | Yes | `info` | `admin_activity_logs` table (Task 3.10) |

**Production API responses for 500:**

```json
{
  "message": "Server Error"
}
```

No `code`, no exception class, no SQL, no file paths.

**Local/testing:** Laravel debug page or expanded JSON is acceptable when `APP_DEBUG=true`.

### What never goes in logs

- Passwords, bearer tokens, reset tokens
- Full credit card or payment instrument details
- Unredacted webhook signing secrets

---

## Rate limiting

Defined in `AppServiceProvider`:

| Limiter | Routes | Response |
|---------|--------|----------|
| `login` | `POST /login` | 429 `too_many_requests` |
| `password-reset` | `POST /forgot-password`, `POST /reset-password` | 429 `too_many_requests` |

---

## Testing errors

| Test type | File | Assert |
|-----------|------|--------|
| Unit (render) | `tests/Feature/Api/ErrorResponseTest.php` | status, `message`, `code` on `ApiException::render()` |
| Feature (401) | Per endpoint | `->assertUnauthorized()` |
| Feature (403) | Per endpoint | `->assertForbidden()` + optional `assertJsonPath('code', '...')` |
| Feature (404) | Isolation tests | `->assertNotFound()` + `assertJsonPath('code', 'not_found')` |
| Feature (422) | Per endpoint | `->assertUnprocessable()` + `assertJsonValidationErrors([...])` |
| Feature (429) | Auth tests | Hit limiter, `->assertStatus(429)` |

Example:

```php
it('returns plan limit exceeded when max clients reached', function () {
    // arrange: freelancer at plan limit
    $this->actingAs($owner, 'sanctum')
        ->withHeader('X-Freelancer-Id', (string) $freelancer->id)
        ->postJson($this->apiUrl('clients'), ['name' => 'New'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'plan_limit_exceeded');
});
```

---

## Agent anti-patterns

- **Never** `return response()->json(['error' => '...'], 400)` — use `ApiException` or validation
- **Never** catch `ApiException` in controllers to swallow or reshape it
- **Never** return `200` with `{ success: false }` — use proper HTTP status
- **Never** expose `$exception->getMessage()` from unhandled exceptions to clients in production
- **Never** use HTTP 400 for validation — use 422
- **Never** use HTTP 404 for "you're not allowed" on same-tenant policy deny — use 403
- **Never** use HTTP 403 for cross-tenant resource ID — use 404
- **Never** skip updating `docs/api/errors.md` when adding `ApiErrorCode` cases

---

## Checklist (per feature)

- [ ] All failure modes listed in endpoint spec `Errors` table
- [ ] Business failures throw `ApiException` with enum case
- [ ] Validation stays in Form Request (no manual `Validator::make` in controllers)
- [ ] Cross-tenant returns `404 not_found`
- [ ] Feature test covers at least one failure mode per endpoint
- [ ] `vendor/bin/sail artisan test --compact tests/Feature/Api/ErrorResponseTest.php` passes when touching error layer
