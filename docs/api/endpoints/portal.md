# Client Portal

Scramble group: **Client Portal** (weight: 33). Read-only client-facing routes. Phase 14.

All routes require `X-Client-Id` header unless the user belongs to exactly one client organization (auto-selected). See [conventions](../conventions.md).

---

### GET /portal/client

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Client-Id` (required when user has multiple client memberships) |
| Middleware | `auth:sanctum`, `client.context` |
| Policy | `ClientMembershipPolicy@viewAny` |

**Response `200`** — [ClientResource](../schemas/client.md) for the active client organization.

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not a client member, archived client, or missing header |

---

### GET /portal/projects

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Client-Id` (required when user has multiple client memberships) |
| Middleware | `auth:sanctum`, `client.context` |
| Policy | `ClientMembershipPolicy@viewAny` |

**Query parameters**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `per_page` | integer | 25 | Max 100 |
| `cursor` | string | — | Cursor pagination |

**Response `200`** — Cursor page of [ProjectResource](../schemas/project.md) for the active client organization.

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not a client member or missing header |

---

### GET /portal/client-invoices

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Client-Id` (required when user has multiple client memberships) |
| Middleware | `auth:sanctum`, `client.context` |
| Policy | `ClientMembershipPolicy@viewAny` |

**Query parameters**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `per_page` | integer | 25 | Max 100 |
| `cursor` | string | — | Cursor pagination |
| `status` | string | — | Filter by invoice status (`sent`, `paid`, `overdue`, etc.) |

Draft and void invoices are excluded from portal results.

**Response `200`** — Cursor page of [ClientInvoiceResource](../schemas/client-invoice.md).

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not a client member or missing header |
