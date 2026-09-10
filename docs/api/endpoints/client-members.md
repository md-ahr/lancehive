# Client Members

Scramble group: **Client Members** (weight: 32). Tenant-scoped. Phase 14.

All routes require `X-Freelancer-Id` header. See [conventions](../conventions.md).

---

### POST /clients/{client}/members

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `ClientMembershipPolicy@create` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | yes | max:255 — used when creating a new user |
| `email` | string | yes | email, max:255 |
| `role` | string | yes | `primary`, `member`, or `viewer` |

**Response `201`** — [ClientMembershipResource](../schemas/membership.md#clientmembershipresource) with nested `user`.

New users receive a password-setup invite email. Existing users receive a client-portal-added notification.

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Member role or non-member |
| 404 | not_found | Client belongs to another workspace |
| 422 | validation_failed | Invalid input or duplicate membership |
