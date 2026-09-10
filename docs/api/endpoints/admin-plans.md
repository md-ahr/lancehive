# Admin — Plans

Scramble group: **Admin** (weight: 10). Super-admin only. Phase 15.3.

---

### GET /admin/plans

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Query parameters**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `is_active` | boolean | — | Filter active plans |

**Response `200`**

```json
{
  "data": [ /* PlanResource[] */ ]
}
```

See [PlanResource](../schemas/plan.md).

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |

---

### POST /admin/plans

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | yes | max:255 |
| `slug` | string | yes | unique:plans |
| `price_monthly` | string | no | decimal |
| `price_yearly` | string | no | decimal |
| `currency` | string | no | size:3, default BDT |
| `max_clients` | integer | no | nullable = unlimited |
| `max_projects` | integer | no | nullable = unlimited |
| `max_team_members` | integer | no | nullable = unlimited |
| `is_custom` | boolean | no | default false |
| `is_active` | boolean | no | default true |
| `sort_order` | integer | no | |

**Response `201`** — [PlanResource](../schemas/plan.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |
| 422 | validation_failed | |

---

### PATCH /admin/plans/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Request body** — any subset of POST fields (all optional on update)

**Response `200`** — [PlanResource](../schemas/plan.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |
| 404 | not_found | |
| 422 | validation_failed | |
