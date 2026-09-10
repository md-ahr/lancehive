# Admin — Reports

Scramble group: **Reports** (weight: 45). Phase 19. Super-admin only.

No `X-Freelancer-Id` required.

Schemas: [report.md](../schemas/report.md).

---

### GET /admin/reports/platform-stats

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

Platform dashboard snapshot.

**Response `200`** — [PlatformStatsResource](../schemas/report.md#platformstatsresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | super_admin_required | |

---

### POST /admin/reports/run

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Middleware | `auth:sanctum`, `can:super-admin` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `report_type` | string | yes | `freelancer_list`, `subscription_revenue` |
| `filters` | object | no | See [Filters](../schemas/report.md#filters-object-json) |
| `per_page` | integer | no | Max 100 |
| `cursor` | string | no | |

**Response `200`** — [ReportRunResource](../schemas/report.md#reportrunresource)

---

### GET /admin/reports

List platform-scoped saved reports (`freelancer_id` null). Cursor paginated.

**Response `200`** — Cursor page of [SavedReportResource](../schemas/report.md#savedreportresource)

---

### POST /admin/reports

Create platform saved report. Same body as workspace `POST /reports`.

**Response `201`** — [SavedReportResource](../schemas/report.md#savedreportresource)

---

### GET /admin/reports/{id}

**Response `200`** — [SavedReportResource](../schemas/report.md#savedreportresource)

---

### PATCH /admin/reports/{id}

Partial update. Super-admin only.

---

### DELETE /admin/reports/{id}

**Response `200`** — message resource.

---

### POST /admin/report-exports

Queue platform CSV export. Logs `report_export_queued` to `admin_activity_logs`.

**Request body:** same as workspace `POST /report-exports`.

**Response `202`** — [ReportExportResource](../schemas/report.md#reportexportresource)

---

### GET /admin/report-exports

List platform exports for the requesting super-admin.

---

### GET /admin/report-exports/{id}

**Response `200`** — [ReportExportResource](../schemas/report.md#reportexportresource)
