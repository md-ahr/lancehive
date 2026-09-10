# LanceHive Architecture

**Applies to:** `app/Features/**`, `app/Core/**`

## Feature modules

Domain code lives under `app/Features/{Feature}/` — Auth, Tenancy, Delivery, ClientBilling, PlatformBilling, Admin, Settings, Reporting. Do not add a flat `app/Services/` dump or `app/Features/.../V1/` while only v1 exists.

Cross-cutting infrastructure only in `app/Core/` (Tenancy, Http, Pagination). Core never imports Features.

## Layer flow (downward only)

HTTP (Controller → Form Request → API Resource) → Application (Action / Service) → Domain (Model, Enum, Policy) → Infrastructure (Job, Listener).

Controllers stay thin. Business rules, plan limits, and invoice math live in Actions/Services — not controllers.

## Module imports

| Feature | May import | Must NOT |
|---------|------------|----------|
| Auth | Core | other Features |
| Tenancy | Auth, Core | Delivery, Billing |
| Delivery | Tenancy, Core | ClientBilling, PlatformBilling |
| ClientBilling | Delivery, Tenancy, Core | PlatformBilling |
| PlatformBilling | Tenancy, Core | ClientBilling, Delivery |
| Reporting | Delivery, ClientBilling, Tenancy, PlatformBilling, Auth, Core | — |

## Tenancy (Phase 2+)

- `freelancer_id` from `TenantContext` only — never request body
- Cross-tenant resource ID → **404** `not_found`; same-tenant policy deny → **403** `forbidden`
- Tenant writes: `auth:sanctum` + `freelancer.context` + `writable.subscription`

Deep reference: `docs/project-structure/feature-based-architecture.md`, `docs/multi-tenant/architecture-overview.md`
