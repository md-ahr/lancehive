# Client Invoices

Scramble group: **Client Invoices** (weight: 50). Tenant-scoped. Phase 8.

---

### POST /projects/{project}/client-invoices

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ClientInvoicePolicy@create` |

Creates draft invoice. Snapshots `bill_to_*` from client. Auto-generates `invoice_number`.

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `due_date` | string | no | date |
| `notes` | string | no | |
| `tax_rate` | string | no | decimal |
| `prefill_unbilled_time` | boolean | no | Pre-fill items from unbilled time logs at `project.hourly_rate` |

**Response `201`** — [ClientInvoiceResource](../schemas/client-invoice.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | validation_failed | |

---

### GET /projects/{project}/client-invoices

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `ClientInvoicePolicy@viewAny` |

**Query parameters** — cursor pagination + optional `status` filter

**Response `200`** — Cursor page of [ClientInvoiceResource](../schemas/client-invoice.md) (list shape, no nested items)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 404 | not_found | |

---

### GET /client-invoices/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |
| Policy | `ClientInvoicePolicy@view` |

Includes items, payments, outstanding balance.

**Response `200`** — [ClientInvoiceResource](../schemas/client-invoice.md) (detail shape)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Member read-only OK |
| 404 | not_found | |

---

### PATCH /client-invoices/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ClientInvoicePolicy@update` |

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `status` | string | no | `draft` → `sent` sets `issued_at`, `sent_at` |
| `due_date` | string | no | date |
| `notes` | string | no | |
| `tax_rate` | string | no | decimal |

**Response `200`** — [ClientInvoiceResource](../schemas/client-invoice.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | invoice_not_editable | Non-draft mutation |
| 422 | validation_failed | |

---

### DELETE /client-invoices/{id}

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ClientInvoicePolicy@delete` |

Only `draft` invoices deletable (soft delete).

**Response `204`** or `200` with message

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | invoice_not_editable | Not draft |

---

### POST /client-invoices/{id}/items

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ClientInvoicePolicy@update` |

Recalculates subtotal, tax, total after each item.

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `description` | string | yes | max:255 |
| `quantity` | string | yes | decimal, min:0 |
| `rate` | string | yes | decimal, min:0 |

**Response `201`** — [ClientInvoiceItemResource](../schemas/client-invoice.md#clientinvoiceitemresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | invoice_not_editable | Not draft |
| 422 | validation_failed | |

---

### POST /client-invoices/{id}/payments

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context`, `writable.subscription` |
| Policy | `ClientInvoicePolicy@update` |

Manual payment record (MVP). Auto-marks invoice `paid` when payments ≥ total.

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `amount` | string | yes | decimal, min:0.01 |
| `payment_method` | string | yes | `manual`, `bank_transfer`, `cash`, `other` |
| `reference` | string | no | |
| `paid_at` | string | yes | datetime |
| `notes` | string | no | |

**Response `201`** — [ClientInvoicePaymentResource](../schemas/client-invoice.md#clientinvoicepaymentresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 403 | workspace_read_only | |
| 404 | not_found | |
| 422 | validation_failed | |
