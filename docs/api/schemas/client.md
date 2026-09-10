# ClientResource

End-customer organization under a freelancer workspace.

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `name` | string | Client organization name |
| `status` | string | `active` or `archived` |
| `contact_email` | string \| null | Primary contact email |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## Example

```json
{
  "id": 10,
  "name": "BigCo Ltd",
  "status": "active",
  "contact_email": "billing@bigco.com",
  "created_at": "2026-03-01T09:00:00+00:00",
  "updated_at": "2026-03-01T09:00:00+00:00"
}
```
