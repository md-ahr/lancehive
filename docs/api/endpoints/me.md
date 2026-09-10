# Me

Scramble group: **Authentication** (weight: 0). Current user profile and workspace context.

---

### GET /me

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (optional — selects active workspace) |
| Middleware | `auth:sanctum` |

**Current behavior (implemented):** returns `{ user: UserResource }`.

**Target behavior (Phase 9.2):** returns [MeResource](../schemas/membership.md#meresource).

**Response `200` (current)**

```json
{
  "user": {
    "id": 1,
    "name": "Jane Owner",
    "email": "jane@example.com",
    "role": "freelancer",
    "email_verified_at": null,
    "created_at": "2026-01-15T10:00:00+00:00",
    "updated_at": "2026-01-15T10:00:00+00:00"
  }
}
```

**Response `200` (Phase 9.2 target)**

```json
{
  "user": { /* UserResource */ },
  "memberships": [ /* FreelancerMembershipResource[] */ ],
  "active_freelancer": { /* FreelancerResource | null */ },
  "subscription": {
    "status": "trialing",
    "plan_name": "Starter",
    "read_only": false,
    "trial_ends_at": "2026-03-24T00:00:00+00:00"
  }
}
```

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | Missing/invalid token |
