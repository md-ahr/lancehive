# Projects

Scramble group: **Projects** (weight: 31). Tenant-scoped. Phase 5 + 7.6.

---

### POST /clients/{client}/projects

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ProjectPolicy@create` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | yes | max:255 |
| `hourly_rate` | string | yes | decimal, min:0 |
| `currency` | string | no | size:3, default BDT |
| `deadline` | string | no | date |
| `status` | string | no | `active`, `on_hold`, `completed` |

**Response `201`** — [ProjectResource](../schemas/project.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | Client not in tenant |
| 422 | validation_failed | |
| 422 | plan_limit_exceeded | max_projects reached |

---

### GET /clients/{client}/projects

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `ProjectPolicy@viewAny` |

**Query parameters** — cursor pagination + optional `status` filter

**Response `200`** — Cursor page of [ProjectResource](../schemas/project.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 404 | not_found | Client not in tenant |

---

### GET /projects

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `ProjectPolicy@viewAny` |

All tenant projects (not scoped to one client).

**Query parameters** — cursor pagination + optional `status`, `client_id` filters

**Response `200`** — Cursor page of [ProjectResource](../schemas/project.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |

---

### GET /projects/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `ProjectPolicy@view` |

**Response `200`** — [ProjectResource](../schemas/project.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 404 | not_found | |

---

### PATCH /projects/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ProjectPolicy@update` |

**Request body** — any subset of create fields (all optional)

**Response `200`** — [ProjectResource](../schemas/project.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | validation_failed | |

---

### DELETE /projects/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ProjectPolicy@delete` |

Soft-deletes project.

**Response `204`** or `200` with message

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |

---

### GET /projects/{id}/time-summary

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `ProjectPolicy@view` |

SQL aggregate — never loads all time logs into memory.

**Response `200`** — [ProjectTimeSummaryResource](../schemas/project.md#projecttimesummaryresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 404 | not_found | |
