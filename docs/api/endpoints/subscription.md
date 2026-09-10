# Subscription

Scramble group: **Subscriptions** (weight: 60). Freelancer owner. Phase 15.

Checkout/swap/cancel routes bypass `writable.subscription` middleware.

---

### GET /subscription

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |

Freelancer owner only.

**Response `200`** — [SubscriptionDetailResource](../schemas/subscription.md#subscriptiondetailresource)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | Not workspace owner |

---

### POST /subscription/checkout

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |

Self-serve plans only (`is_custom = false`).

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `plan_id` | integer | yes | exists:plans, not custom |
| `billing_interval` | string | yes | `monthly` or `yearly` |

**Response `200`** — [CheckoutResource](../schemas/subscription.md#checkoutresource)

```json
{ "checkout_url": "https://checkout.stripe.com/..." }
```

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 422 | validation_failed | Custom plan or invalid interval |

---

### POST /subscription/swap

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |

Change plan or billing interval (Stripe proration).

**Request body**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `plan_id` | integer | yes | exists:plans |
| `billing_interval` | string | yes | `monthly` or `yearly` |

**Response `200`** — [SubscriptionResource](../schemas/subscription.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
| 422 | validation_failed | |

---

### POST /subscription/cancel

| | |
|---|---|
| Auth | `Bearer` (Sanctum) |
| Headers | `X-Freelancer-Id` (required) |
| Middleware | `auth:sanctum`, `freelancer.context` |

Cancel at period end. Sets `canceled_at`.

**Request body** — none

**Response `200`** — [SubscriptionResource](../schemas/subscription.md)

**Errors**

| HTTP | code | When |
|------|------|------|
| 401 | unauthenticated | |
| 403 | forbidden | |
