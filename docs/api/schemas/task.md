# TaskResource

Unit of work inside a project.

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `project_id` | integer | Parent project |
| `title` | string | Task title |
| `status` | string | `todo`, `in_progress`, or `done` |
| `due_date` | string \| null | ISO 8601 date |
| `estimated_hours` | string \| null | Decimal string |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## Example

```json
{
  "id": 30,
  "project_id": 20,
  "title": "Homepage mockup",
  "status": "in_progress",
  "due_date": "2026-03-20",
  "estimated_hours": "8.00",
  "created_at": "2026-03-06T11:00:00+00:00",
  "updated_at": "2026-03-08T14:30:00+00:00"
}
```
