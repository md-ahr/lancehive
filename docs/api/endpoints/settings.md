# Settings

Scramble group: **Settings** (weight: 15). Phase 18.

Three scopes with role-based access — see [security-and-auth.md](../../development/security-and-auth.md#settings-authorization).

Contract schemas: [settings.md](../schemas/settings.md).

---

### GET /me/settings

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum` |

Returns the authenticated user's preferences (timezone, locale, notification toggles).

**Response `200`** — [UserSettingsResource](../schemas/settings.md#usersettingsresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | Missing/invalid token |

---

### PATCH /me/settings

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum` |

Partial update — only sent fields change.

**Request body** (all optional)

| Field | Type | Rules |
|-------|------|-------|
| `timezone` | string | Valid IANA timezone |
| `locale` | string | `en` (MVP allowlist) |
| `notification_preferences` | object | Keys: `subscription_alerts`, `workspace_invites`, `invoice_activity` (booleans) |

**Response `200`** — [UserSettingsResource](../schemas/settings.md#usersettingsresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 422 | validation_failed | Invalid timezone, locale, or preference shape |

---

### GET /workspace/settings

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required when user has multiple memberships) |
| Middleware | `auth:sanctum`, `freelancer.context` |

Any workspace **member** may read.

**Response `200`** — [WorkspaceSettingsResource](../schemas/settings.md#workspacesettingsresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not a workspace member |

---

### PATCH /workspace/settings

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required when user has multiple memberships) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |

Workspace **owner** or **admin** only.

**Request body** (all optional)

| Field | Type | Rules |
|-------|------|-------|
| `default_currency` | string | size:3, valid ISO 4217 |
| `invoice_number_prefix` | string | max:20, alphanumeric + hyphen |
| `default_tax_rate` | string \| null | decimal 0–100 |
| `invoice_footer_notes` | string \| null | max:5000 |
| `business_name` | string \| null | max:255 |
| `business_email` | string \| null | email, max:255 |
| `business_address` | string \| null | max:5000 |

**Response `200`** — [WorkspaceSettingsResource](../schemas/settings.md#workspacesettingsresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Member role, or read-only subscription |
| 403 | workspace_read_only | Expired trial / unpaid subscription |
| 422 | validation_failed | Invalid field values |

---

### GET /admin/settings

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Response `200`** — [PlatformSettingsResource](../schemas/settings.md#platformsettingsresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not super-admin |

---

### PATCH /admin/settings

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

Partial update. Invalidates `PlatformSettingsCache` on success.

**Request body** (all optional)

| Field | Type | Rules |
|-------|------|-------|
| `default_trial_days` | integer | min:1, max:90 |
| `default_plan_slug` | string | exists:plans,slug |
| `support_email` | string | email, max:255 |
| `maintenance_mode` | boolean | |

**Response `200`** — [PlatformSettingsResource](../schemas/settings.md#platformsettingsresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 422 | validation_failed | Unknown plan slug, invalid email |
