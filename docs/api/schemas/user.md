# UserResource

Authenticated user profile. Never includes `password` or `remember_token`.

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `name` | string | Display name |
| `email` | string | Email address |
| `role` | string | `super_admin`, `freelancer`, or `client` |
| `email_verified_at` | string \| null | ISO 8601 datetime |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## Example

```json
{
  "id": 1,
  "name": "Jane Owner",
  "email": "jane@example.com",
  "role": "freelancer",
  "email_verified_at": null,
  "created_at": "2026-01-15T10:00:00+00:00",
  "updated_at": "2026-01-15T10:00:00+00:00"
}
```

## LoginResource

Returned by `POST /login`.

| Field | Type | Description |
|-------|------|-------------|
| `token` | string | Sanctum bearer token |
| `user` | object | [UserResource](#userresource) |

## MessageResource

Simple action confirmation.

| Field | Type | Description |
|-------|------|-------------|
| `message` | string | Human-readable confirmation |
