# Webhooks

Scramble group: **Webhooks** (weight: 70). Payment provider only. Phase 15.9.

---

### POST /webhooks/stripe

| | |
|---|---|
| Auth | None — verified via Stripe signature header |
| Middleware | `stripe.webhook` (Cashier) |

**Headers**

| Header | Required | Description |
|--------|----------|-------------|
| `Stripe-Signature` | yes | Webhook signature for verification |

**Request body** — Raw Stripe event JSON (not documented field-by-field; see Stripe docs)

**Handled events**

| Event | Action |
|-------|--------|
| `checkout.session.completed` | Activate subscription |
| `invoice.paid` | Record `SubscriptionCharge`, set `active` |
| `invoice.payment_failed` | Set `past_due` |
| `customer.subscription.updated` | Sync status, period dates |
| `customer.subscription.deleted` | Set `read_only` or `canceled` |

**Response `200`**

```json
{ "message": "Webhook handled." }
```

**Errors**

| HTTP | code | When |
|------|------|------|
| 400 | — | Invalid signature or payload |
| 500 | — | Processing failure (Stripe will retry) |

**Notes**

- Not callable from frontend — Stripe servers only
- Invalidates `SubscriptionCache` on state change
- Creates `SubscriptionCharge` records on successful payment
