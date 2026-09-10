# Design Rationale & Scaling Path

Why this architecture, what breaks at growth, known weaknesses, and **cheap MVP habits** that keep future scaling smooth — without building big-scale infrastructure now.

Related: [architecture-overview.md](./architecture-overview.md) · [architecture-review.md](./architecture-review.md) · [implementation-tasks.md](./implementation-tasks.md)

---

## 1. Why this structure (not a simpler one)

### Simpler alternatives we rejected

| Simpler design | Problem for LanceHive |
|----------------|----------------------|
| **User → Projects** (no Client / Freelancer) | Freelancers serve multiple client orgs. A string `client_name` on projects breaks invoicing, plan limits (`max_clients`), and reporting. |
| **One `Invoice` model** | Two money flows: client → freelancer (`ClientInvoice*`) vs freelancer → platform (`Subscription*`). One table causes policy bugs and Stripe webhook confusion. |
| **Database per tenant** | Strong isolation, but migrations × N workspaces, backup complexity, connection pooling — overkill before product validation. |
| **JSON / wide rows** | Fewer tables today; god-row queries, no indexes, painful time-log and invoice math tomorrow. |
| **No denormalized `freelancer_id`** | Every tenant filter joins `projects → clients → freelancers`. Extra joins on every list API. |

### What this structure optimizes for

```
Freelancer (tenant) → Client → Project → Task → TimeLog
                              └── ClientInvoice (separate billing module)
Freelancer → Subscription → Plan (platform billing)
```

- **Matches the business:** super-admin onboards workspaces; freelancers manage client orgs; projects carry agreed `hourly_rate`.
- **Single DB + row-level tenancy:** smallest ops surface for MVP; standard Laravel patterns.
- **Separate billing domains:** code clarity beats table count.
- **Denormalized tenant keys where lists are hot:** `projects.freelancer_id`, `client_invoices.freelancer_id` — validated on write, indexed for read.

**Principle:** More tables than a todo app, fewer than retrofitting tenancy and dual billing onto a flat model later.

---

## 2. Growth expectations (no big-scale build now)

Do **not** add read replicas, partitioning, or warehouses in MVP. Architect so those are **additive**, not a rewrite.

### Which tables grow fastest

| Table | Growth rate | First pain without guardrails |
|-------|-------------|-------------------------------|
| `time_logs` | **Highest** | Unpaginated lists; loading all logs to sum hours |
| `client_invoices` + items | Medium | Unscoped admin reports |
| `tasks`, `projects` | Moderate | Plan limits cap per-tenant size early |
| `freelancers`, `users` | Low | Unlikely bottleneck for years |

### Scaling ladder (when to act)

| Stage | Trigger (rough) | Action — **later**, not MVP |
|-------|-----------------|------------------------------|
| **MVP** | Launch | Tenant-scoped queries, indexes (ERD §8), cursor pagination, queued jobs for cron |
| **Growth** | Slow list APIs; `time_logs` > ~500k rows | Archive job for logs > 2 years; cap eager-loads; report endpoints async |
| **Scale** | Hot tenant or platform-wide analytics | Read replica for reports; optional `time_logs` partition by `logged_at`; warehouse export |

**At ~1M `time_logs`:** Fine if every query filters through tenant/project scope and uses indexes. Breaks on unscoped `SELECT *`, dashboard aggregates without queues, or N+1 loading entire project histories.

---

## 3. Known weaknesses & mitigations

| Weakness | Risk | MVP mitigation (build now) | Future option (don't build yet) |
|----------|------|----------------------------|----------------------------------|
| Row-level tenancy in one DB | Scope bug leaks data | Global scopes, policies, Phase 11 isolation tests; ban raw unscoped queries | Per-tenant DB for enterprise tier |
| Denormalized `freelancer_id` | Drift from `clients` | Validate in form requests / services on create-update | DB trigger (avoid in MVP) |
| Soft FK `time_logs ↔ invoice items` | Wrong billing order | `ClientInvoiceService` owns insert order; document in Task 2.11 | — |
| Plan limits in app | Race at limit boundary | `PlanLimitService` inside DB transaction + `lockForUpdate` on subscription row | Redis counter |
| Shared DB noisy neighbor | One tenant hogs CPU | Queue heavy work; paginate all lists; no sync platform-wide aggregates in request cycle | Rate limits per tenant |
| Read-only on lapse | Users can export data | Product choice — document in overview | Hard block (if ever needed) |
| Manual client payments | Ops burden | Accept for MVP | bKash / bank (Phase 16) |
| Indirect scope on Task/TimeLog | Forgotten tenant filter | `BelongsToTenantViaProject` trait; never query `TimeLog::` without scope in controllers | Optional `freelancer_id` on `time_logs` if tenant-wide log reports need it |
| Super-admin tenant override | Abuse / no audit trail | Log to `admin_activity_logs` (Task 3.10) | Full audit UI (Phase 16.2) |

---

## 4. MVP guardrails (smooth scale later, cheap today)

These are **required conventions** from Phase 2 onward — not optional polish.

### 4.1 Query rules

- All tenant data access goes through Eloquent models with global scopes — **never** `DB::table('clients')` in controllers.
- Super-admin cross-tenant reads use explicit `Freelancer::find()` + policy, not disabled scopes by default.
- Aggregates (`SUM(hours)`, invoice totals) use **SQL aggregates** or service methods — never load all rows into PHP collections.

### 4.2 API pagination

All list endpoints return paginated results. See [architecture-overview.md § API pagination](./architecture-overview.md#api-pagination-standard).

| Endpoint type | Pagination |
|---------------|------------|
| Clients, projects, tasks, invoices | Cursor (`?cursor=`) — default `per_page=25`, max `100` |
| Time logs | **Required** cursor pagination — highest volume |
| Admin freelancer list | Offset OK at low volume; switch to cursor if slow |

### 4.3 Background work

- Cron-style work (`MarkOverdueClientInvoices`, subscription sync side effects) → **queued jobs**, not inline in HTTP requests.
- Future report exports → job + notification pattern from day one (even if report is simple).

### 4.4 API versioning (folders)

- URLs: `/api/v1/...` — route files in `routes/features/v1/` only.
- Domain (`app/Features/`), migrations, and tests are **not** versioned in folder paths.
- Do not add `V1/` under `app/Features/` until v2 needs a divergent HTTP contract.

See [feature-based-architecture.md §8.1](../project-structure/feature-based-architecture.md#81-versioning-vs-folder-structure).

### 4.5 Files and blobs

- Invoice PDFs (Phase 16.4): store on disk/S3 via path column — **never** bytea in PostgreSQL.

### 4.6 Indexes for future archive

- `time_logs.logged_at` indexed (Task 1.28) — supports date-range queries and future archive job (`WHERE logged_at < ?`).

### 4.7 Caching (Redis, not PostgreSQL)

**Decision:** Use **Redis** for application cache. Keep **PostgreSQL** for persistent data only.

| Option | Verdict | Plain English |
|--------|---------|---------------|
| **Redis** | **Use this** | In-memory store built for fast temporary data. Already in your Docker stack. |
| **PostgreSQL `cache` table** | **Avoid in production** | Every cache read/write hits the same database that stores clients, projects, and invoices — slows both. |
| **File cache** | Local fallback only | OK without Docker; not for multi-server production. |

**Cache in MVP:** active `plans`, `freelancer:{id}:subscription` (for write middleware), `user:{id}:freelancer_memberships` (for `/me`).

**Do not cache:** plan limit counts (accuracy > speed — use DB transaction), tenant CRUD lists, invoices, time logs.

**Tests:** `CACHE_STORE=array` in PHPUnit — no Redis required in CI.

---

## 5. What we explicitly defer

| Capability | Why defer | How design stays ready |
|------------|-----------|------------------------|
| Read replicas | Ops cost | Reports via jobs; no sync cross-tenant aggregates in controllers |
| Table partitioning | Premature | `logged_at` index + archive job path documented |
| DB per tenant | Complexity | Tenant key on all owned rows; isolation tests |
| Redis cache | Use from MVP for plans/subscription/`/me` | Same Redis in Sail; keeps load off Postgres |
| Event sourcing | Over-engineering | `SubscriptionCharge` + invoice snapshots for audit history |
| Search engine (Meilisearch) | Low volume | `ILIKE` + indexes on name fields sufficient for MVP |

---

## 6. Implementation mapping

| Guardrail | Task |
|-----------|------|
| Cursor pagination + API conventions | **2.12** |
| Redis cache config + env defaults | **2.13** |
| Plan / subscription / membership cache services | **2.14** |
| Admin activity log (super-admin override) | **3.10** |
| Plan limit transaction lock | **15.11** (updated) |
| Performance indexes incl. `logged_at` | **1.28** |
| Isolation tests | **11.1 – 11.5** |
| Service boundaries | **2.11** |
| Archive / replica / partition | **Phase 17** (future) |

---

## 7. Decision summary

| Question | Answer |
|----------|--------|
| Why not simpler? | Multi-tenant + dual billing + client orgs need explicit boundaries now. |
| 1M rows? | OK with tenant scope + pagination + indexes; bad without them. |
| Weaknesses? | Scope bugs, denormalization drift, shared DB — mitigated by MVP guardrails above. |
| Big scale now? | **No.** Build habits and indexes so Phase 17 is additive, not a rewrite. |
