# Clients

Scramble group: **Clients** (weight: 30). Tenant-scoped. Phase 4.

All routes require `X-Freelancer-Id` header. See [conventions](../conventions.md).

---

### GET /clients

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `ClientPolicy@viewAny` |

**Query parameters**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `per_page` | integer | 25 | Max 100 |
| `cursor` | string | — | Cursor pagination |
| `status` | string | — | `active` or `archived` |

**Response `200`** — Cursor page of [ClientResource](../schemas/client.md). See [pagination](../schemas/pagination.md).

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not a workspace member |

---

### POST /clients

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ClientPolicy@create` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | yes | max:255 |
| `contact_email` | string | no | email |

**Response `201`** — [ClientResource](../schemas/client.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | Subscription lapsed |
| 422 | validation_failed | |
| 422 | plan_limit_exceeded | max_clients reached |

---

### GET /clients/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `ClientPolicy@view` |

**Response `200`** — [ClientResource](../schemas/client.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 404 | not_found | Wrong tenant or missing |

---

### PATCH /clients/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ClientPolicy@update` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | no | max:255 |
| `contact_email` | string | no | email, nullable |
| `status` | string | no | `active`, `archived` |

**Response `200`** — [ClientResource](../schemas/client.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | validation_failed | |

---

### DELETE /clients/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ClientPolicy@delete` |

Archives client (status → `archived`). Projects remain.

**Response `200`** — [MessageResource](../schemas/user.md#messageresource) or `204 No Content`

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
