# TimeLogResource

Hours logged by a workspace member against a task.

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `task_id` | integer | Parent task |
| `user_id` | integer | Who logged the time |
| `hours` | string | Decimal string, e.g. `"2.50"` |
| `description` | string \| null | Work description |
| `logged_at` | string | ISO 8601 datetime — when work was performed |
| `client_invoice_item_id` | integer \| null | Set when billed |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## Example

```json
{
  "id": 40,
  "task_id": 30,
  "user_id": 12,
  "hours": "2.50",
  "description": "Initial wireframes",
  "logged_at": "2026-03-08T09:00:00+00:00",
  "client_invoice_item_id": null,
  "created_at": "2026-03-08T14:00:00+00:00",
  "updated_at": "2026-03-08T14:00:00+00:00"
}
```
