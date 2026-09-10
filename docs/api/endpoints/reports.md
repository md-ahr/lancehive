# Reports (workspace)

Scramble group: **Reports** (weight: 45). Phase 19.

Middleware on all routes: `auth:sanctum`, `freelancer.context`. Send `X-Freelancer-Id`.

Schemas: [report.md](../schemas/report.md).

**Authorization:** Any member may read stats and run reports. Owner/admin required to create, update, delete saved reports and queue exports.

---

### GET /workspace/stats

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `freelancer.context` |

Sync dashboard snapshot for the active workspace.

**Response `200`** — [WorkspaceStatsResource](../schemas/report.md#workspacestatsresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not a workspace member |

---

### POST /reports/run

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `freelancer.context` |

Run an ad-hoc report. Returns SQL summary plus cursor-paginated preview rows.

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `report_type` | string | yes | `time_logs`, `unbilled_work`, `client_invoices` |
| `filters` | object | no | See [Filters](../schemas/report.md#filters-object-json) |
| `per_page` | integer | no | Default 25, max 100 |
| `cursor` | string | no | Cursor pagination |

**Response `200`** — [ReportRunResource](../schemas/report.md#reportrunresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not a member |
| 404 | not_found | Filter references another tenant's client/project |
| 422 | validation_failed | Invalid type or filters |

---

### GET /reports

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `freelancer.context` |

List saved reports for the active workspace.

**Query:** `per_page`, `cursor` — see [pagination](../schemas/pagination.md).

**Response `200`** — Cursor page of [SavedReportResource](../schemas/report.md#savedreportresource)

---

### POST /reports

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `freelancer.context` |

**Request body**

| Field | Type | Required |
|-------|------|----------|
| `name` | string | yes |
| `report_type` | string | yes |
| `filters` | object | no |

**Response `201`** — [SavedReportResource](../schemas/report.md#savedreportresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 403 | forbidden | Member role (owner/admin required) |

---

### GET /reports/{id}

**Response `200`** — [SavedReportResource](../schemas/report.md#savedreportresource)

**Errors:** 404 cross-tenant or unknown id.

---

### PATCH /reports/{id}

Partial update of `name` and/or `filters`. Owner/admin only.

**Response `200`** — [SavedReportResource](../schemas/report.md#savedreportresource)

---

### DELETE /reports/{id}

**Response `200`** — `{ "message": "..." }`

Owner/admin only.

---

### POST /report-exports

Queue async CSV export. Owner/admin only.

**Request body**

| Field | Type | Required |
|-------|------|----------|
| `report_type` | string | yes |
| `filters` | object | no |
| `format` | string | yes — `csv` only |
| `saved_report_id` | integer | no |

**Response `202`** — [ReportExportResource](../schemas/report.md#reportexportresource) with `status: pending`

---

### GET /report-exports

List exports requested by the authenticated user in the active workspace. Cursor paginated.

**Response `200`** — Cursor page of [ReportExportResource](../schemas/report.md#reportexportresource)

---

### GET /report-exports/{id}

**Response `200`** — [ReportExportResource](../schemas/report.md#reportexportresource) including `download_url` when completed.

**Errors**

| HTTP | code | When |
|------|------|------|
| 404 | not_found | Another user's export or cross-tenant |
| 410 | export_expired | Past `expires_at` (if implemented) |
