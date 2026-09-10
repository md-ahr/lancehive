# Members

Scramble group: **Members** (weight: 25). Tenant-scoped. Phase 13.

All routes require `X-Freelancer-Id` header. See [conventions](../conventions.md).

---

### GET /members

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `FreelancerMembershipPolicy@viewAny` |

**Query parameters**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `per_page` | integer | 25 | Max 100 |
| `cursor` | string | — | Cursor pagination |

**Response `200`** — Cursor page of [FreelancerMembershipResource](../schemas/membership.md) with nested `user`.

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not a workspace member |

---

### POST /members

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `FreelancerMembershipPolicy@create` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | yes | max:255 — used when creating a new user |
| `email` | string | yes | email, max:255 |
| `role` | string | yes | `admin` or `member` |

**Response `201`** — [FreelancerMembershipResource](../schemas/membership.md) with nested `user`.

New users receive a password-setup invite email. Existing users receive a workspace-added notification.

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Member role or non-member |
| 422 | validation_failed | Invalid input or duplicate membership |
| 422 | plan_limit_exceeded | `max_team_members` reached |

---

### DELETE /members/{membership}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `FreelancerMembershipPolicy@delete` |

**Response `200`** — `MessageResource`

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Member role, removing owner, or admin removing admin |
| 404 | not_found | Membership belongs to another workspace |
