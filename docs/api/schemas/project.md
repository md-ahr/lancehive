# ProjectResource

Work engagement under a client. Includes billable `hourly_rate`.

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `client_id` | integer | Parent client |
| `name` | string | Project name |
| `hourly_rate` | string | Decimal string, e.g. `"1500.00"` |
| `currency` | string | ISO 4217, default `BDT` |
| `status` | string | `active`, `on_hold`, or `completed` |
| `deadline` | string \| null | ISO 8601 date |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## ProjectTimeSummaryResource

Returned by `GET /projects/{id}/time-summary`.

| Field | Type | Description |
|-------|------|-------------|
| `project_id` | integer | Project ID |
| `total_hours` | string | Sum of all time logs (decimal string) |
| `billed_hours` | string | Hours linked to invoice items |
| `unbilled_hours` | string | Hours not yet invoiced |

## Example

```json
{
  "id": 20,
  "client_id": 10,
  "name": "Website Redesign",
  "hourly_rate": "1500.00",
  "currency": "BDT",
  "status": "active",
  "deadline": "2026-06-30",
  "created_at": "2026-03-05T10:00:00+00:00",
  "updated_at": "2026-03-05T10:00:00+00:00"
}
```
