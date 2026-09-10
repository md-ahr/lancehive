# Settings resources

Three scopes: **user** (cross-workspace), **workspace** (per freelancer tenant), **platform** (super-admin singleton).

Scramble group: **Settings** (weight: 15).

---

## UserSettingsResource

Returned by `GET/PATCH /me/settings`.

| Field | Type | Description |
|-------|------|-------------|
| `timezone` | string | IANA timezone identifier (e.g. `UTC`, `Asia/Dhaka`) |
| `locale` | string | MVP allowlist: `en` |
| `notification_preferences` | object | See [notification_preferences](#notification_preferences) |

### notification_preferences

| Key | Type | Default | Role gate |
|-----|------|---------|-----------|
| `subscription_alerts` | boolean | `true` | Honored only when user is workspace **owner** |
| `workspace_invites` | boolean | `true` | All roles |
| `invoice_activity` | boolean | `true` | Owner/admin only (when invoice notifications exist) |

### Example

```json
{
  "timezone": "Asia/Dhaka",
  "locale": "en",
  "notification_preferences": {
    "subscription_alerts": true,
    "workspace_invites": true,
    "invoice_activity": true
  }
}
```

---

## WorkspaceSettingsResource

Returned by `GET/PATCH /workspace/settings`. Resolved from active `X-Freelancer-Id` tenant — no `freelancer_id` in body.

| Field | Type | Description |
|-------|------|-------------|
| `default_currency` | string | ISO 4217 (3 chars), default `BDT` |
| `invoice_number_prefix` | string | Alphanumeric + hyphen, max 20, default `INV` |
| `default_tax_rate` | string \| null | Decimal `5,2` — pre-fill for new draft invoices |
| `invoice_footer_notes` | string \| null | Default footer on new invoices |
| `business_name` | string \| null | Bill-from name on invoices |
| `business_email` | string \| null | Bill-from email |
| `business_address` | string \| null | Bill-from address |

### Example

```json
{
  "default_currency": "BDT",
  "invoice_number_prefix": "INV",
  "default_tax_rate": null,
  "invoice_footer_notes": null,
  "business_name": "Acme Studio",
  "business_email": "billing@acme.test",
  "business_address": "Dhaka, Bangladesh"
}
```

---

## PlatformSettingsResource

Returned by `GET/PATCH /admin/settings`. Singleton row (`platform_settings.id = 1`).

| Field | Type | Description |
|-------|------|-------------|
| `default_trial_days` | integer | Fallback when onboarding omits `trial_days` (1–90) |
| `default_plan_slug` | string | Must match `plans.slug` (e.g. `starter`) |
| `support_email` | string | Shown in app and transactional emails |
| `maintenance_mode` | boolean | Stored and returned; enforcement deferred to Phase 16+ |

### Example

```json
{
  "default_trial_days": 14,
  "default_plan_slug": "starter",
  "support_email": "support@lancehive.com",
  "maintenance_mode": false
}
```

---

## MeResource extension (Phase 18.14)

Optional summary on `GET /me` — timezone and locale only (full preferences remain on `/me/settings`).

| Field | Type | Description |
|-------|------|-------------|
| `user_settings` | object \| null | `{ "timezone": "...", "locale": "..." }` |
