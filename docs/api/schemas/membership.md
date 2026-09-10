# FreelancerMembershipResource

Links a user to a freelancer workspace.

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `freelancer_id` | integer | Workspace ID |
| `user_id` | integer | User ID |
| `role` | string | `owner`, `admin`, or `member` |
| `freelancer` | object \| null | Nested [FreelancerResource](./freelancer.md) when loaded |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## Example

```json
{
  "id": 3,
  "freelancer_id": 5,
  "user_id": 12,
  "role": "owner",
  "freelancer": {
    "id": 5,
    "name": "Acme Studio",
    "slug": "acme-studio",
    "status": "active",
    "owner_user_id": 12,
    "created_at": "2026-02-01T08:00:00+00:00",
    "updated_at": "2026-02-01T08:00:00+00:00"
  },
  "created_at": "2026-02-01T08:00:00+00:00",
  "updated_at": "2026-02-01T08:00:00+00:00"
}
```

## MeResource

Returned by enhanced `GET /me` (Phase 9).

| Field | Type | Description |
|-------|------|-------------|
| `user` | object | [UserResource](./user.md) |
| `memberships` | array | [FreelancerMembershipResource](#freelancermembershipresource)[] |
| `active_freelancer` | object \| null | [FreelancerResource](./freelancer.md) from `X-Freelancer-Id` |
| `subscription` | object \| null | Subscription summary (see below) |

### subscription (summary on /me)

| Field | Type | Description |
|-------|------|-------------|
| `status` | string | `trialing`, `active`, `past_due`, `read_only`, `canceled` |
| `plan_name` | string | Current plan name |
| `read_only` | boolean | `true` when the workspace subscription blocks writes |
| `trial_ends_at` | string \| null | ISO 8601 datetime |
