# Report schemas

Phase 19 — Reporting. Design source for API Resources; runtime truth is Scramble `/docs/api.json`.

---

## ReportType (request enum)

| Value | Scope |
|-------|-------|
| `workspace_overview` | Workspace — used by `GET /workspace/stats` only |
| `time_logs` | Workspace |
| `unbilled_work` | Workspace |
| `client_invoices` | Workspace |
| `platform_overview` | Platform — used by `GET /admin/reports/platform-stats` only |
| `freelancer_list` | Platform |
| `subscription_revenue` | Platform |

---

## Filters object (JSON)

Used in `POST /reports/run`, saved reports, and exports. Keys validated per `report_type`; unknown keys stripped.

| Key | Type | Used by |
|-----|------|---------|
| `from` | date (`Y-m-d`) | time_logs, client_invoices, subscription_revenue |
| `to` | date | same |
| `client_id` | integer | time_logs, unbilled_work, client_invoices |
| `project_id` | integer | time_logs, unbilled_work, client_invoices |
| `user_id` | integer | time_logs |
| `status` | string | client_invoices, freelancer_list, subscription_revenue |
| `plan_id` | integer | freelancer_list, subscription_revenue |

Cross-tenant `client_id` / `project_id` → **404** `not_found`.

---

## WorkspaceStatsResource

Returned by `GET /workspace/stats`.

| Field | Type | Description |
|-------|------|-------------|
| `active_clients` | integer | Non-archived clients |
| `active_projects` | integer | Non-deleted projects with status `active` |
| `hours_this_month` | string | Decimal string, hours logged in current UTC month |
| `unbilled_hours` | string | Decimal string, logs without `client_invoice_item_id` |
| `outstanding_invoice_total` | string | Decimal string, sum of sent + overdue invoice totals minus payments |

---

## PlatformStatsResource

Returned by `GET /admin/reports/platform-stats`.

| Field | Type | Description |
|-------|------|-------------|
| `freelancers_by_status` | object | Keys: `pending`, `active`, `suspended` → counts |
| `subscriptions_by_plan` | array | `{ plan_id, plan_name, count }` |
| `subscriptions_by_status` | object | Subscription status → count |
| `trials_ending_soon` | integer | Trials ending within 7 days |

---

## ReportRunResource

Returned by `POST /reports/run` and `POST /admin/reports/run`.

| Field | Type | Description |
|-------|------|-------------|
| `report_type` | string | Echo request type |
| `filters` | object | Normalized filters |
| `summary` | object | Type-specific aggregate keys |
| `preview` | cursor page | Row shape depends on type — see endpoints doc |

### summary by type

**time_logs:** `total_hours`, `billed_hours`, `unbilled_hours`

**unbilled_work:** `total_unbilled_hours`, `estimated_revenue`, `currency` (workspace default)

**client_invoices:** `count_by_status`, `total_by_status`, `outstanding_total`

**freelancer_list:** `count_by_status`

**subscription_revenue:** `paid_total`, `currency`, `charge_count`

---

## SavedReportResource

| Field | Type |
|-------|------|
| `id` | integer |
| `name` | string |
| `report_type` | string |
| `filters` | object |
| `created_by_user_id` | integer |
| `created_at` | datetime |
| `updated_at` | datetime |

Platform saved reports omit `freelancer_id` in response (implicit null).

---

## ReportExportResource

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | |
| `report_type` | string | |
| `format` | string | `csv` |
| `status` | string | `pending`, `processing`, `completed`, `failed` |
| `row_count` | integer \| null | Set when completed |
| `download_url` | string \| null | Present when `status` is `completed` and not expired |
| `expires_at` | datetime | |
| `completed_at` | datetime \| null | |
| `error_message` | string \| null | When `failed` |

---

## ExportFormat

| Value | Status |
|-------|--------|
| `csv` | Supported |
| `pdf` | Deferred post-MVP |
