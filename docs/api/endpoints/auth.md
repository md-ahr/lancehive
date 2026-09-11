# Authentication

Scramble group: **Authentication** (weight: 0). Public routes except `logout` and enhanced `me`.

---

### POST /login

| | |
|---|---|
| Auth | None (public) |
| Middleware | `throttle:login` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `email` | string | yes | email |
| `password` | string | yes | |

**Response `200`** — [LoginResource](../schemas/user.md#loginresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 422 | validation_failed | Missing/invalid email or password format |
| 422 | validation_failed | Wrong credentials (`errors.email`) — same message when account is locked |
| 429 | too_many_requests | Rate limit exceeded |

---

### POST /logout

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum` |

**Request body** — none

**Response `200`** — [MessageResource](../schemas/user.md#messageresource)

```json
{ "message": "Logged out successfully." }
```

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | Missing/invalid token |

---

### POST /forgot-password

| | |
|---|---|
| Auth | None (public) |
| Middleware | `throttle:password-reset` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `email` | string | yes | email |

**Response `200`** — [MessageResource](../schemas/user.md#messageresource)

Always returns success message (no email enumeration).

**Errors**

| HTTP | code | When |
|------|------|------|
| 422 | validation_failed | Invalid email format |
| 429 | too_many_requests | Rate limit exceeded |

---

### POST /reset-password

| | |
|---|---|
| Auth | None (public) |
| Middleware | `throttle:password-reset` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `token` | string | yes | Password reset token |
| `email` | string | yes | email |
| `password` | string | yes | confirmed, min:8 |
| `password_confirmation` | string | yes | |

**Response `200`** — [MessageResource](../schemas/user.md#messageresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 422 | validation_failed | Invalid token, email, or password |
| 429 | too_many_requests | Rate limit exceeded |

---

### GET /users

| | |
|---|---|
| Auth | `Bearer` (Sanctum) · super-admin only |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Query parameters**

| Param | Type | Default | Rules |
|-------|------|---------|-------|
| `per_page` | integer | `25` | 1–100 |
| `cursor` | string | — | Cursor from previous page `meta.next_cursor` |

**Response `200`** — cursor-paginated [UserResource](../schemas/user.md#userresource) list

```json
{
  "data": [
    { "id": 1, "name": "...", "email": "...", "role": "user" }
  ],
  "links": {
    "first": "...",
    "last": null,
    "prev": null,
    "next": "..."
  },
  "meta": {
    "path": "...",
    "per_page": 25,
    "next_cursor": "...",
    "prev_cursor": null
  }
}
```

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | Missing/invalid/expired token |
| 403 | super_admin_required | Non-admin |
| 422 | validation_failed | `per_page` out of range |
