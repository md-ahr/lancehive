# Admin — Freelancers

Scramble group: **Admin** (weight: 10). Super-admin only. Phase 3.

---

### GET /admin/freelancers

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Query parameters**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `per_page` | integer | 25 | Max 100 |
| `cursor` | string | — | Cursor pagination |
| `status` | string | — | Filter: `pending`, `active`, `suspended` |

**Response `200`** — Cursor page of [FreelancerResource](../schemas/freelancer.md). See [pagination](../schemas/pagination.md).

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |

---

### GET /admin/freelancers/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Response `200`** — [FreelancerDetailResource](../schemas/freelancer.md#freelancerdetailresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |
| 404 | not_found | Invalid ID |

---

### POST /admin/freelancers

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

Creates workspace + owner user + membership + trialing subscription (transaction).

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `workspace_name` | string | yes | max:255 |
| `owner_name` | string | yes | max:255 |
| `owner_email` | string | yes | email, unique:users |
| `plan_id` | integer | no | exists:plans |
| `trial_days` | integer | no | min:1, max:90 |

**Response `201`** — [FreelancerDetailResource](../schemas/freelancer.md#freelancerdetailresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |
| 422 | validation_failed | Invalid payload |

---

### PATCH /admin/freelancers/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `status` | string | yes | `pending`, `active`, `suspended` |

**Response `200`** — [FreelancerResource](../schemas/freelancer.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |
| 404 | not_found | |
| 422 | validation_failed | |

---

### POST /admin/freelancers/{id}/resend-invite

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Request body** — none

**Response `200`** — [MessageResource](../schemas/user.md#messageresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |
| 404 | not_found | |

---

### PATCH /admin/freelancers/{id}/subscription

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

Super-admin assigns custom plan. Phase 15.12.

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `plan_id` | integer | yes | exists:plans |
| `provider` | string | no | `manual` (default) or `stripe` |

**Response `200`** — [SubscriptionResource](../schemas/subscription.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |
| 404 | not_found | |
| 422 | validation_failed | |
