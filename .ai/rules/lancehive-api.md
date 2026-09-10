# LanceHive API

**Applies to:** `routes/**`, `config/api.php`, `config/scramble.php`, `app/Features/**/Http/**`

## Versioning

- URL prefix: `/api/v1` from `config/api.php` (`API_ROUTE_VERSION=v1`)
- Route files: `routes/features/v1/{feature}.php`, required in `routes/api.php` via `config('api.features_routes')`
- Domain code is **not** versioned — no `V1/` under `app/Features/`

## HTTP rules

- Form Request validation; API Resource responses — no raw `response()->json()` on new endpoints
- Business failures: `throw new ApiException(ApiErrorCode::...)` from Action/Service
- Validation failures: **422** Laravel envelope — no `code` field
- New routes: `#[Group]` on controller + assert path in `tests/Feature/Api/DocumentationTest.php`

## Middleware groups

| Group | Middleware |
|-------|------------|
| Public auth | none |
| Authenticated | `auth:sanctum` |
| Tenant read | `auth:sanctum`, `freelancer.context` |
| Tenant write | above + `writable.subscription` |
| Admin | `auth:sanctum`, super-admin gate |

Contract docs: `docs/api/README.md`, `docs/api/errors.md`. Skills: `lancehive-api-docs`, `lancehive-api-contract`.
