# SubscriptionResource

Platform billing relationship for a freelancer workspace.

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `freelancer_id` | integer | Workspace ID |
| `plan_id` | integer | Current plan |
| `status` | string | `trialing`, `active`, `past_due`, `read_only`, `canceled` |
| `billing_interval` | string \| null | `monthly` or `yearly`; null during trial |
| `trial_ends_at` | string \| null | ISO 8601 datetime |
| `current_period_start` | string \| null | ISO 8601 datetime |
| `current_period_end` | string \| null | ISO 8601 datetime |
| `read_only_at` | string \| null | When workspace was downgraded |
| `canceled_at` | string \| null | ISO 8601 datetime |
| `provider` | string | `stripe` or `manual` |
| `plan` | object \| null | Nested [PlanResource](./plan.md) |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## SubscriptionDetailResource

Returned by `GET /subscription` (freelancer owner).

| Field | Type | Description |
|-------|------|-------------|
| *(all SubscriptionResource fields)* | | |
| `days_remaining` | integer \| null | Days until trial/period end |
| `usage` | object | Current counts vs plan limits |

### usage

| Field | Type | Description |
|-------|------|-------------|
| `clients` | integer | Current client count |
| `projects` | integer | Current project count |
| `team_members` | integer | Current membership count |
| `max_clients` | integer \| null | From plan |
| `max_projects` | integer \| null | From plan |
| `max_team_members` | integer \| null | From plan |

## CheckoutResource

Returned by `POST /subscription/checkout`.

| Field | Type | Description |
|-------|------|-------------|
| `checkout_url` | string | Stripe Checkout session URL |

## Example

```json
{
  "id": 7,
  "freelancer_id": 5,
  "plan_id": 1,
  "status": "trialing",
  "billing_interval": null,
  "trial_ends_at": "2026-03-24T00:00:00+00:00",
  "current_period_start": null,
  "current_period_end": null,
  "read_only_at": null,
  "canceled_at": null,
  "provider": "stripe",
  "plan": {
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
  },
  "created_at": "2026-02-01T08:00:00+00:00",
  "updated_at": "2026-02-01T08:00:00+00:00"
}
```
