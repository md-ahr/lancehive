# Multi-Tenant Freelancer Platform

Documentation for LanceHive's freelancer workspace architecture.

| Document | Purpose |
|----------|---------|
| [Architecture Overview](./architecture-overview.md) | Domain model, tenancy, billing, and API design |
| [System Architecture Diagrams](./system-architecture-diagram.md) | Visual diagrams — stack, flows, billing |
| [Database ERD](./database-erd.md) | Full entity relationship diagram and table catalog |
| [Architecture Review](./architecture-review.md) | Red flags audit — indexes, layers, dependencies |
| [Design Rationale & Scaling](./design-rationale-and-scaling.md) | Why this shape, growth path, MVP guardrails |
| [Final Architecture Audit](./final-architecture-audit.md) | Full 5-area review with severity-ranked issues |
| [Implementation Tasks](./implementation-tasks.md) | Step-by-step task breakdown (build in order) |

**Project layout:** [Feature-based file structure](../project-structure/README.md) — folders, layers, module isolation.

## Quick context

- **You (super-admin)** onboard freelancers as isolated workspaces (tenants).
- Each **freelancer** manages **clients** (customer organizations).
- Each **client** has **projects**; projects contain **tasks** and **time logs**.
- Each **project** has an `hourly_rate` set when the freelancer creates it for a client.
- Freelancers **invoice their clients** via `ClientInvoice` → `ClientInvoiceItem` → `ClientInvoicePayment` (record-only in MVP).
- Freelancers **pay you monthly/yearly** via `Plan` → `Subscription` → `SubscriptionCharge` (BDT, plan limits on clients/projects).
- Expired subscription → workspace **read-only** (not hard blocked).
- **Custom plan** — freelancer contacts you for unlimited clients/projects.
- **MVP:** single database, row-level isolation via `freelancer_id`; cursor pagination and tenant query rules from day one (see [design-rationale-and-scaling.md](./design-rationale-and-scaling.md)).
- **API:** versioned at `/api/v1` — route files in `routes/features/v1/`; `app/Features/` and tests are not folder-versioned. See [architecture-overview § API versioning](./architecture-overview.md#api-versioning) and [feature-based-architecture §8.1](../project-structure/feature-based-architecture.md#81-versioning-vs-folder-structure).
- **Cache:** Redis for plans, subscription status, and `/me` memberships — PostgreSQL stays the data store only ([architecture-overview § Caching](./architecture-overview.md#caching-strategy)).
- **Client portal login:** designed now, shipped in a later phase.

## Domain at a glance

```
Freelancer (tenant)
└── Client
    └── Project (hourly_rate)
        ├── Task → TimeLog
        └── ClientInvoice → ClientInvoiceItem, ClientInvoicePayment

Freelancer → Subscription → Plan   (platform billing — you charge freelancers)
                                   → SubscriptionCharge
```

## How to build

Work through [implementation-tasks.md](./implementation-tasks.md) in order. Each task is intentionally small — one PR or one focused session. Do not skip tenant isolation tests (Phase 11).
