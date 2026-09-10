# Time Logs

Scramble group: **Time Logs** (weight: 33). Tenant-scoped. Phase 7.

---

### POST /tasks/{task}/time-logs

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `TimeLogPolicy@create` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `hours` | string | yes | decimal, min:0.01, max:24 |
| `description` | string | no | max:1000 |
| `logged_at` | string | yes | datetime |

**Response `201`** — [TimeLogResource](../schemas/time-log.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | Task not in tenant |
| 422 | validation_failed | |

---

### GET /tasks/{task}/time-logs

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `TimeLogPolicy@viewAny` |

**Query parameters** — cursor pagination (required for high volume)

**Response `200`** — Cursor page of [TimeLogResource](../schemas/time-log.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 404 | not_found | |

---

### PATCH /time-logs/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `TimeLogPolicy@update` |

Member can only edit own time logs.

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `hours` | string | no | decimal |
| `description` | string | no | |
| `logged_at` | string | no | datetime |

**Response `200`** — [TimeLogResource](../schemas/time-log.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not owner of time log |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | validation_failed | Billed time log cannot be edited |

---

### DELETE /time-logs/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `TimeLogPolicy@delete` |

**Response `204`** or `200` with message

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | validation_failed | Billed time log cannot be deleted |
