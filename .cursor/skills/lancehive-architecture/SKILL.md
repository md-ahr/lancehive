---
name: lancehive-architecture
description: Use when designing features, choosing module placement, reviewing dependencies, or answering how LanceHive tenancy and billing fit together. Triggers on architecture, module boundaries, where to put code, or multi-tenant design questions.
---

# LanceHive Architecture

## Domain (30-second model)

```
Super-admin → Freelancer (tenant) → Client → Project → Task → TimeLog
                              └── ClientInvoice* (client billing)
Freelancer → Subscription → Plan (platform billing)
```

## Two billing layers (never mix names)

| Layer | Models |
|-------|--------|
| Client billing | `ClientInvoice`, `ClientInvoiceItem`, `ClientInvoicePayment` |
| Platform billing | `Plan`, `Subscription`, `SubscriptionCharge` |

## Feature modules

`Auth` · `Tenancy` · `Delivery` · `ClientBilling` · `PlatformBilling` · `Admin` · `ClientPortal` (later)

Dependency graph: Tenancy → Auth; Delivery → Tenancy; ClientBilling → Delivery + Tenancy; PlatformBilling → Tenancy; Admin → all via services.

## Layer rules

- Invoice math only in `ClientInvoiceService` (ClientBilling).
- Plan limits only in `PlanLimitService` (PlatformBilling) with `lockForUpdate`.
- Tenant filtering in `app/Core/Tenancy/`; authorization in Policies.

## API versioning & folders

| Versioned | Not versioned |
|-----------|---------------|
| `routes/features/v1/*.php` | `app/Features/{Feature}/` |
| `/api/v1` URLs | `database/`, migrations, factories |
| Scramble `api_path` | `tests/Feature/{Feature}/` — use `$this->apiUrl()` |

Config: `config/api.php` · env `API_ROUTE_VERSION=v1`. No `V1/` under `app/Features/` in MVP.

v2 later: `routes/features/v2/` + optional `V2/` controllers only when HTTP contracts break. Freeze v1.

Detail: `docs/project-structure/feature-based-architecture.md` §8.1

## MVP guardrails

- Row-level tenancy via global scopes + policies + Phase 11 isolation tests.
- Cursor pagination on all list endpoints; SQL aggregates for sums.
- Expired subscription → read-only workspace (GET ok, writes blocked).
- Redis cache: plans, subscription, memberships — not tenant data lists.

## Full docs (read one file, not all)

| Topic | Path |
|-------|------|
| File layout | `docs/project-structure/feature-based-architecture.md` |
| Domain detail | `docs/multi-tenant/architecture-overview.md` |
| Scaling rules | `docs/multi-tenant/design-rationale-and-scaling.md` |
| Audit / indexes | `docs/multi-tenant/architecture-review.md` |
