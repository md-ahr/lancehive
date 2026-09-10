# Users

Scramble group: **Users** (weight: 1). Super-admin only.

---

### GET /users

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |
| Policy | Super-admin gate |

**Query parameters** — none (full list, no pagination in MVP)

**Response `200`**

```json
{
  "users": [
    { /* UserResource */ }
  ]
}
```

See [UserResource](../schemas/user.md).

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | Missing/invalid token |
| 403 | super_admin_required | Non-admin user |
