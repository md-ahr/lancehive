# Project Structure

Documentation for how LanceHive organizes files and folders using **feature-based architecture** with **strict layer isolation**.

| Document | Purpose |
|----------|---------|
| [Feature-Based Architecture](./feature-based-architecture.md) | Target folder layout, layers, modules, naming, and dependency rules |
| [Migration Guide](./migration-from-flat-laravel.md) | How to move from the current flat `app/` layout to the target structure |

## Related docs

- [Multi-tenant architecture](../multi-tenant/architecture-overview.md) — domain model, tenancy, billing
- [Architecture review](../multi-tenant/architecture-review.md) — module dependency graph and layer rules
- [Implementation tasks](../multi-tenant/implementation-tasks.md) — build order (Phase 2+ assumes this structure)

## Principles (short)

1. **Organize by feature, not by technical type alone** — `ClientBilling`, `Delivery`, `Tenancy`, not a single giant `Services/` dump.
2. **One direction of dependencies** — HTTP → Application → Domain → Infrastructure. No upward or circular imports.
3. **Each feature owns its use cases** — controllers stay thin; business rules live in Actions/Services inside the feature.
4. **Shared code is explicit** — cross-cutting pieces live in `app/Core/`, not copied across features.
5. **Tests mirror features** — `tests/Feature/ClientBilling/` matches `app/Features/ClientBilling/` (not versioned in path — use `$this->apiUrl()`).
6. **API versioned in routes only** — URLs under `/api/v1`; route files in `routes/features/v1/`; domain code in `app/Features/` is **not** versioned. See [§8.1](./feature-based-architecture.md#81-versioning-vs-folder-structure).

## Feature modules (business boundaries)

These map 1:1 to the dependency graph in [architecture-review.md](../multi-tenant/architecture-review.md):

```
Auth
  └── Tenancy
        ├── Delivery          (clients, projects, tasks, time logs)
        ├── ClientBilling     (ClientInvoice*)
        ├── PlatformBilling   (Plan, Subscription*)
        ├── Admin             (super-admin panel API)
        └── ClientPortal      (Phase 14 — read-only client API)
```

Start reading: [feature-based-architecture.md](./feature-based-architecture.md).
