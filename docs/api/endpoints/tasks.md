# Tasks

Scramble group: **Tasks** (weight: 32). Tenant-scoped. Phase 6.

---

### POST /projects/{project}/tasks

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `TaskPolicy@create` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `title` | string | yes | max:255 |
| `status` | string | no | `todo`, `in_progress`, `done` |
| `due_date` | string | no | date |
| `estimated_hours` | string | no | decimal, min:0 |

**Response `201`** — [TaskResource](../schemas/task.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | Project not in tenant |
| 422 | validation_failed | |

---

### GET /projects/{project}/tasks

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `TaskPolicy@viewAny` |

**Query parameters** — cursor pagination + optional `status` filter

**Response `200`** — Cursor page of [TaskResource](../schemas/task.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 404 | not_found | |

---

### GET /tasks/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `TaskPolicy@view` |

**Response `200`** — [TaskResource](../schemas/task.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 404 | not_found | |

---

### PATCH /tasks/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `TaskPolicy@update` |

**Request body** — any subset of create fields (all optional)

**Response `200`** — [TaskResource](../schemas/task.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | validation_failed | |

---

### DELETE /tasks/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `TaskPolicy@delete` |

Soft-deletes task.

**Response `204`** or `200` with message

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
