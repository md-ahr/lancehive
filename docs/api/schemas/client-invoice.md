# ClientInvoiceResource

Freelancer-to-client billing document.

## Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `project_id` | integer | Parent project |
| `invoice_number` | string | e.g. `INV-2026-0001` |
| `status` | string | `draft`, `sent`, `paid`, `overdue`, `void` |
| `currency` | string | ISO 4217, default `BDT` |
| `subtotal` | string | Decimal string |
| `tax_rate` | string \| null | e.g. `"0.00"` |
| `tax_amount` | string | Decimal string |
| `total` | string | Decimal string |
| `issued_at` | string \| null | ISO 8601 date |
| `due_date` | string \| null | ISO 8601 date |
| `sent_at` | string \| null | ISO 8601 datetime |
| `paid_at` | string \| null | ISO 8601 datetime |
| `notes` | string \| null | Footer / payment instructions |
| `bill_to_name` | string | Snapshot from client |
| `bill_to_email` | string \| null | Snapshot from client |
| `bill_to_address` | string \| null | Billing address snapshot |
| `outstanding_balance` | string | Computed: `total - sum(payments)` (detail view only) |
| `items` | array | [ClientInvoiceItemResource](#clientinvoiceitemresource)[] (detail view) |
| `payments` | array | [ClientInvoicePaymentResource](#clientinvoicepaymentresource)[] (detail view) |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## ClientInvoiceItemResource

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `description` | string | Line item description |
| `quantity` | string | Decimal string (hours or units) |
| `rate` | string | Decimal string |
| `amount` | string | `quantity × rate` |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## ClientInvoicePaymentResource

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Primary key |
| `amount` | string | Decimal string |
| `payment_method` | string | `manual`, `bank_transfer`, `cash`, `other` |
| `reference` | string \| null | Transaction / bank reference |
| `paid_at` | string | ISO 8601 datetime |
| `notes` | string \| null | |
| `created_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

## Lifecycle rules

| Action | Allowed when |
|--------|--------------|
| Update fields | `status = draft` only (except status transition to `sent`) |
| Add items | `status = draft` |
| Record payment | `status` is `sent`, `overdue`, or `paid` |
| Delete | `status = draft` only (soft delete) |
| `draft` → `sent` | Sets `issued_at`, `sent_at` |

## Example

```json
{
  "id": 50,
  "project_id": 20,
  "invoice_number": "INV-2026-0001",
  "status": "draft",
  "currency": "BDT",
  "subtotal": "7500.00",
  "tax_rate": "0.00",
  "tax_amount": "0.00",
  "total": "7500.00",
  "issued_at": null,
  "due_date": "2026-04-15",
  "sent_at": null,
  "paid_at": null,
  "notes": "Payment via bank transfer.",
  "bill_to_name": "BigCo Ltd",
  "bill_to_email": "billing@bigco.com",
  "bill_to_address": null,
  "created_at": "2026-03-10T10:00:00+00:00",
  "updated_at": "2026-03-10T10:00:00+00:00"
}
```
