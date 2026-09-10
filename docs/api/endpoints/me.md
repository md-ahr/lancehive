# Me

Scramble group: **Authentication** (weight: 0). Current user profile and workspace context.

---

### GET /me

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (optional), `X-Client-Id` (optional) |
| Middleware | `auth:sanctum` |

Returns [MeResource](../schemas/membership.md#meresource) with the authenticated user, freelancer memberships, active workspace, subscription summary, client memberships, and active client organization.

When `X-Freelancer-Id` is omitted and the user belongs to exactly one workspace, that workspace is used for `active_freelancer` and `subscription`. When the user belongs to multiple workspaces and no header is sent, those fields are `null`.

When `X-Client-Id` is omitted and the user belongs to exactly one client organization, that client is used for `active_client`. When the user belongs to multiple client organizations and no header is sent, `active_client` is `null`.

**Response `200`**

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
  },
  "client_memberships": [ /* ClientMembershipResource[] */ ],
  "active_client": { /* ClientResource | null */ }
}
```

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | Missing/invalid token |
| 403 | forbidden | `X-Freelancer-Id` or `X-Client-Id` points to an organization the user does not belong to |
