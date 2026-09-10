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
| 422 | validation_failed | Wrong credentials (`errors.email`) |
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
