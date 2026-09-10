# PlanResource

Platform pricing tier managed by super-admin.

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `name` | string | e.g. `Starter`, `Pro` |
| `slug` | string | URL-safe unique identifier |
| `price_monthly` | string \| null | BDT decimal string; null for custom |
| `price_yearly` | string \| null | BDT decimal string; null for custom |
| `currency` | string | Default `BDT` |
| `max_clients` | integer \| null | `null` = unlimited |
| `max_projects` | integer \| null | `null` = unlimited |
| `max_team_members` | integer \| null | `null` = unlimited |
| `is_custom` | boolean | `true` = not self-serve |
| `is_active` | boolean | Visible for new signups |
| `sort_order` | integer | Display order |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## Example

```json
{
  "id": 1,
  "name": "Starter",
  "slug": "starter",
  "price_monthly": "200.00",
  "price_yearly": "2000.00",
  "currency": "BDT",
  "max_clients": 3,
  "max_projects": 5,
  "max_team_members": 1,
  "is_custom": false,
  "is_active": true,
  "sort_order": 1,
  "created_at": "2026-01-01T00:00:00+00:00",
  "updated_at": "2026-01-01T00:00:00+00:00"
}
```
