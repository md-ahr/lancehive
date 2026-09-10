# FreelancerResource

Freelancer workspace (tenant).

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `name` | string | Workspace display name |
| `slug` | string | URL-safe unique identifier |
| `status` | string | `pending`, `active`, or `suspended` |
| `owner_user_id` | integer | FK to workspace owner |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## FreelancerDetailResource

Extended shape for `GET /admin/freelancers/{id}`.

| Field | Type | Description |
|-------|------|-------------|
| *(all FreelancerResource fields)* | | |
| `owner` | object \| null | [UserResource](./user.md) |
| `member_count` | integer | Active memberships count |
| `subscription_summary` | object \| null | See below |

### subscription_summary

| Field | Type | Description |
|-------|------|-------------|
| `status` | string | Subscription status enum |
| `plan_name` | string | Current plan name |
| `trial_ends_at` | string \| null | ISO 8601 datetime |

## Example

```json
{
  "id": 5,
  "name": "Acme Studio",
  "slug": "acme-studio",
  "status": "active",
  "owner_user_id": 12,
  "created_at": "2026-02-01T08:00:00+00:00",
  "updated_at": "2026-02-01T08:00:00+00:00"
}
```
