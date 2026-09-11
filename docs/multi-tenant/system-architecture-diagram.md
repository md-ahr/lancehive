# LanceHive System Architecture Diagrams

Visual reference for the multi-tenant freelancer platform. Complements [architecture-overview.md](./architecture-overview.md). Design rationale and scale path: [design-rationale-and-scaling.md](./design-rationale-and-scaling.md).

---

## 0. How components talk to each other

Overview of every major interaction path — who calls whom, and in what order.

### 0.1 Component map (all connections)

```mermaid
flowchart TB
    subgraph users [Users]
        SA[Super Admin]
        FO[Freelancer Owner]
        FM[Freelancer Member]
    end

    subgraph edge [Edge]
        FE[Frontend App]
    end

    subgraph api [Laravel API Layer]
        Routes[Routes / Controllers]
        Sanctum[Sanctum - token auth]
        EFC[EnsureFreelancerContext]
        EWS[EnsureWritableSubscription]
        Policies[Policies]
        TC[TenantContext - singleton]
        PLS[PlanLimitService]
        SOS[FreelancerOnboardingService]
        SubSvc[SubscriptionService]
        InvSvc[ClientInvoiceService]
    end

    subgraph async [Async]
        Queue[Laravel Queue]
        Jobs[Jobs - overdue invoices etc]
        Notif[Notifications / Mail]
    end

    subgraph storage [Storage]
        DB[(PostgreSQL)]
    end

    subgraph third [Third Party]
        Stripe[Stripe]
    end

    SA --> FE
    FO --> FE
    FM --> FE
    FE -->|"HTTPS JSON + Bearer token"| Routes
    FE -->|"X-Freelancer-Id header"| Routes

    Routes --> Sanctum
    Sanctum --> EFC
    EFC --> TC
    EFC --> EWS
    EWS --> TC
    EWS --> PLS
    Routes --> Policies
    Policies --> TC

    Routes --> SOS
    Routes --> SubSvc
    Routes --> InvSvc

    SOS --> DB
    SubSvc --> DB
    InvSvc --> DB
    PLS --> DB
    TC --> DB
    Routes --> DB

    SubSvc <-->|checkout URL + webhooks| Stripe
    Stripe -->|"POST /webhooks/stripe"| Routes

    SOS --> Notif
    SubSvc --> Notif
    Routes --> Queue
    Queue --> Jobs
    Jobs --> DB
    Notif --> Mail[Resend / Mailpit SMTP]
```

### 0.2 Authenticated read request (e.g. list projects)

```mermaid
sequenceDiagram
    autonumber
    participant FE as Frontend
    participant R as Router
    participant S as Sanctum
    participant EFC as EnsureFreelancerContext
    participant TC as TenantContext
    participant C as ProjectController
    participant P as ProjectPolicy
    participant M as Project Model
    participant DB as PostgreSQL

    FE->>R: GET /projects + Authorization Bearer
    R->>S: Validate API token
    S->>DB: Load User from token
    S-->>R: User OK
    R->>EFC: Resolve tenant
    EFC->>DB: FreelancerMembership for user + X-Freelancer-Id
    EFC->>TC: setFreelancerId(12)
    Note over EFC: EnsureWritableSubscription SKIPPED on GET
    R->>C: index()
    C->>P: viewAny(user)
    P-->>C: allowed
    C->>M: Project query
    M->>TC: Global scope adds WHERE freelancer_id = 12
    M->>DB: SELECT projects
    DB-->>FE: 200 JSON list
```

### 0.3 Authenticated write request (e.g. create project)

```mermaid
sequenceDiagram
    autonumber
    participant FE as Frontend
    participant R as Router
    participant S as Sanctum
    participant EFC as EnsureFreelancerContext
    participant EWS as EnsureWritableSubscription
    participant TC as TenantContext
    participant PLS as PlanLimitService
    participant C as ProjectController
    participant Pol as ProjectPolicy
    participant M as Project Model
    participant DB as PostgreSQL

    FE->>R: POST /clients/5/projects + hourly_rate
    R->>S: Validate token
    S-->>R: User OK
    R->>EFC: Resolve tenant
    EFC->>TC: setFreelancerId(12)
    R->>EWS: Can workspace write?
    EWS->>DB: Subscription status for freelancer 12
    alt status is read_only or canceled
        EWS-->>FE: 403 Workspace is read-only
    else trialing or active
        EWS-->>R: OK
        R->>C: store()
        C->>PLS: assertCanCreateProject(freelancer)
        PLS->>DB: COUNT projects vs plan.max_projects
        alt limit exceeded
            PLS-->>FE: 422 Plan limit reached
        else within limit
            C->>Pol: create(user, client)
            Pol-->>C: allowed
            C->>M: create with freelancer_id from TC
            M->>DB: INSERT project
            DB-->>FE: 201 Project created
        end
    end
```

### 0.4 Super-admin onboard freelancer

```mermaid
sequenceDiagram
    autonumber
    participant SA as Super Admin
    participant FE as Frontend
    participant AC as Admin FreelancerController
    participant SOS as FreelancerOnboardingService
    participant DB as PostgreSQL
    participant N as Notification
    participant Mail as Email

    SA->>FE: Create workspace form
    FE->>AC: POST /admin/freelancers
    AC->>SOS: onboard(workspace_name, owner_email)
    SOS->>DB: BEGIN TRANSACTION
    SOS->>DB: INSERT freelancer
    SOS->>DB: INSERT or find user
    SOS->>DB: INSERT freelancer_membership owner
    SOS->>DB: INSERT subscription trialing + starter plan
    SOS->>DB: COMMIT
    SOS->>N: FreelancerInvited
    N->>Mail: Set-password link
    AC-->>FE: 201 workspace + owner
```

### 0.5 Platform subscription checkout (freelancer pays you)

```mermaid
sequenceDiagram
    autonumber
    participant FO as Freelancer Owner
    participant FE as Frontend
    participant SC as SubscriptionController
    participant SS as SubscriptionService
    participant DB as PostgreSQL
    participant Stripe as Stripe
    participant WH as WebhookController

    FO->>FE: Choose plan + monthly/yearly
    FE->>SC: POST /subscription/checkout
    SC->>SS: createCheckoutSession()
    SS->>DB: Load plan + subscription
    SS->>Stripe: Create Checkout Session
    Stripe-->>FE: Redirect to Stripe hosted page
    FO->>Stripe: Pay
    Stripe->>WH: POST /webhooks/stripe checkout.session.completed
    WH->>SS: syncFromWebhook()
    SS->>DB: subscription.status = active
    SS->>DB: INSERT subscription_charge paid
    WH-->>Stripe: 200 OK
    FO->>FE: Return to app - full write access
```

### 0.6 Client invoice and manual payment (MVP)

```mermaid
sequenceDiagram
    autonumber
    participant FM as Freelancer Member
    participant FE as Frontend
    participant IC as ClientInvoiceController
    participant IS as ClientInvoiceService
    participant DB as PostgreSQL
    participant Client as End Client offline

    FM->>FE: Create invoice for project
    FE->>IC: POST /projects/3/client-invoices
    IC->>IS: createDraft(project)
    IS->>DB: INSERT client_invoice + bill_to snapshot
    IS->>DB: INSERT items from time_logs x hourly_rate
    IC-->>FE: 201 draft invoice

    FM->>FE: Mark sent
    FE->>IC: PATCH status=sent
    IS->>DB: UPDATE issued_at sent_at

    Note over Client: Client pays offline bank or cash
    FM->>FE: Record payment received
    FE->>IC: POST /client-invoices/9/payments
    IC->>DB: INSERT client_invoice_payment
    IC->>DB: UPDATE invoice status paid if total met
```

### 0.7 Communication rules (quick reference)

| From | To | Protocol | When |
|------|-----|----------|------|
| Frontend | Laravel API | HTTPS + JSON | Every user action |
| Frontend | Laravel API | `Authorization: Bearer {token}` | After login |
| Frontend | Laravel API | `X-Freelancer-Id: {id}` | User has multiple workspaces |
| Sanctum | PostgreSQL | SQL | Validate token → load User |
| EnsureFreelancerContext | TenantContext | In-memory | Every tenant route |
| EnsureFreelancerContext | PostgreSQL | SQL | Verify membership |
| EnsureWritableSubscription | PostgreSQL | SQL | Every tenant **write** route |
| PlanLimitService | PostgreSQL | SQL | Before create client/project/member |
| Policies | TenantContext | In-memory | Every authorized action |
| Models | TenantContext | Global scope | Auto-filter by freelancer_id |
| SubscriptionService | Stripe | HTTPS REST | Checkout, swap, cancel |
| Stripe | WebhookController | HTTPS POST | Payment events |
| OnboardingService | Mail | Resend (prod) / Mailpit (local) | Invite emails |
| Scheduler | Queue | Internal | Daily overdue invoice job |
| Queue Worker | PostgreSQL | SQL | Async job execution |

---

## 1. High-level system architecture

```mermaid
flowchart TB
    subgraph clients [Clients]
        WebApp[Web App / Frontend]
        Mobile[Mobile Browser]
    end

    subgraph lancehive [LanceHive Platform]
        API[Laravel 13 API]
        Sanctum[Sanctum Auth]
        MW1[EnsureFreelancerContext]
        MW2[EnsureWritableSubscription]
        TC[TenantContext]
        PL[PlanLimitService]
        Policies[Laravel Policies]
        API --> Sanctum
        Sanctum --> MW1
        MW1 --> MW2
        MW2 --> TC
        MW2 --> PL
        MW2 --> Policies
    end

    subgraph data [Data Layer]
        PG[(PostgreSQL / Neon)]
        Redis[(Redis - app cache)]
        Queue[Queue / Jobs - database MVP]
    end

    subgraph external [External Services]
        Stripe[Stripe - platform billing]
        Mail[Email - invites / notifications]
        BKash[bKash - Phase 16]
    end

    WebApp --> API
    Mobile --> API
    API --> PG
    API --> Redis
    API --> Queue
    Queue --> PG
    MW2 --> Redis
    PL --> Redis
    API <-->|checkout + webhooks| Stripe
    API --> Mail
    API -.->|future| BKash
```

---

## 2. Actor and API surface map

```mermaid
flowchart LR
    subgraph actors [Actors]
        SA[Super Admin]
        FO[Freelancer Owner]
        FM[Freelancer Member]
        CM[Client Member - Phase 14]
    end

    subgraph routes [API Namespaces]
        Admin["/admin/*"]
        Tenant["/clients /projects /tasks /time-logs"]
        Invoices["/client-invoices/*"]
        Sub["/subscription/*"]
        Portal["/portal/*"]
        Hooks["/webhooks/stripe"]
        Auth["/login /me /logout"]
    end

    SA --> Admin
    SA --> Auth
    FO --> Tenant
    FO --> Invoices
    FO --> Sub
    FO --> Auth
    FM --> Tenant
    FM --> Invoices
    FM --> Auth
    CM --> Portal
    CM --> Auth
    Stripe[Stripe] --> Hooks
```

---

## 3. Multi-tenant domain hierarchy

```mermaid
flowchart TB
    Platform[LanceHive Platform]

    Platform --> SA[Super Admin]
    Platform --> Plans[Plans + Subscriptions]

    Platform --> F1[Freelancer Workspace A]
    Platform --> F2[Freelancer Workspace B]

    F1 --> Sub1[Subscription]
    Sub1 --> Plan1[Plan - Starter 200 BDT]

    F1 --> C1[Client - Acme Corp]
    F1 --> C2[Client - Beta Ltd]

    C1 --> P1[Project - Website Redesign]
    P1 --> HR1["hourly_rate: 1500 BDT"]

    P1 --> T1[Task]
    P1 --> T2[Task]
    T1 --> TL1[TimeLog]
    T2 --> TL2[TimeLog]

    P1 --> CI1[ClientInvoice]
    CI1 --> CII1[ClientInvoiceItem]
    CI1 --> CIP1[ClientInvoicePayment]

    F1 --> FM1[FreelancerMembership - Owner]
    F1 --> FM2[FreelancerMembership - Member]
```

---

## 4. Two billing layers (separate money flows)

```mermaid
flowchart TB
    subgraph platformBilling [Platform Billing - Freelancer pays LanceHive]
        direction TB
        Plan[Plan]
        Subscription[Subscription]
        SubCharge[SubscriptionCharge]
        Stripe[Stripe / Manual]

        FreelancerWS[Freelancer Workspace] -->|monthly or yearly| Subscription
        Plan --> Subscription
        Subscription --> SubCharge
        Subscription <-->|webhooks| Stripe
        SuperAdmin[Super Admin] -->|manages| Plan
        SuperAdmin -->|assigns custom plan| Subscription
    end

    subgraph clientBilling [Client Billing - Client pays Freelancer]
        direction TB
        Project[Project]
        ClientInv[ClientInvoice]
        ClientItem[ClientInvoiceItem]
        ClientPay[ClientInvoicePayment]
        EndClient[End Client - offline]

        Project -->|hourly_rate| ClientInv
        ClientInv --> ClientItem
        ClientInv --> ClientPay
        EndClient -->|bank / cash / bKash later| ClientPay
        FreelancerUser[Freelancer User] -->|records payment MVP| ClientPay
    end

    FreelancerWS -.-> FreelancerUser
```

---

## 5. Request flow (tenant-scoped write)

```mermaid
sequenceDiagram
    participant U as Freelancer User
    participant API as Laravel API
    participant Auth as Sanctum
    participant TCtx as TenantContext
    participant SubMW as EnsureWritableSubscription
    participant PL as PlanLimitService
    participant Policy as ProjectPolicy
    participant DB as PostgreSQL

    U->>API: POST /clients/5/projects + Bearer token + X-Freelancer-Id
    API->>Auth: Validate token
    Auth-->>API: User authenticated
    API->>TCtx: Resolve freelancer_id from membership
    TCtx-->>API: freelancer_id = 12
    API->>SubMW: Check subscription writable
    alt subscription read_only
        SubMW-->>U: 403 Read-only workspace
    else active or trialing
        SubMW-->>API: OK
        API->>PL: Check max_projects for plan
        alt limit exceeded
            PL-->>U: 422 Plan limit reached
        else within limit
            PL-->>API: OK
            API->>Policy: authorize create project
            Policy-->>API: allowed
            API->>DB: INSERT project with freelancer_id + hourly_rate
            DB-->>U: 201 Project created
        end
    end
```

---

## 6. Onboarding and subscription lifecycle

```mermaid
flowchart TB
    Start([Super Admin onboard freelancer]) --> CreateWS[Create Freelancer workspace]
    CreateWS --> CreateUser[Create / link owner User]
    CreateUser --> CreateMem[FreelancerMembership - owner]
    CreateMem --> CreateSub[Subscription - trialing + Starter plan]
    CreateSub --> SendInvite[Send invite email]

    SendInvite --> OwnerLogin[Owner sets password + logs in]
    OwnerLogin --> TrialUse[Full write access during trial]

    TrialUse --> TrialCheck{Trial ending?}
    TrialCheck -->|Self-serve plan| Checkout[POST /subscription/checkout]
    TrialCheck -->|Custom plan| AdminAssign[Super Admin assigns plan manually]

    Checkout --> StripePay[Stripe payment]
    StripePay --> Webhook[Webhook: subscription.active]
    Webhook --> ActiveSub[Subscription status = active]

    AdminAssign --> ActiveSub

    TrialCheck -->|No payment| ReadOnly[Subscription status = read_only]
    ActiveSub --> RenewFail{Renewal failed?}
    RenewFail -->|Yes| PastDue[past_due then read_only]
    RenewFail -->|No| ActiveSub

    ReadOnly --> ViewOnly[GET allowed / POST blocked]
    ViewOnly --> Resubscribe[Owner resubscribes]
    Resubscribe --> ActiveSub
```

---

## 7. Entity relationship (data model)

> **Full ERD with all columns, indexes, and table catalog:** [database-erd.md](./database-erd.md)

```mermaid
erDiagram
    User ||--o{ FreelancerMembership : has
    User ||--o{ TimeLog : logs
    User ||--o{ ClientMembership : "Phase 14"

    Freelancer ||--o{ FreelancerMembership : has
    Freelancer ||--o| Subscription : has
    Freelancer ||--o{ Client : owns
    Freelancer ||--o{ Project : scopes
    Freelancer ||--o{ ClientInvoice : scopes

    Plan ||--o{ Subscription : offers
    Subscription ||--o{ SubscriptionCharge : records

    Client ||--o{ Project : has
    Client ||--o{ ClientMembership : "Phase 14"

    Project ||--o{ Task : has
    Project ||--o{ ClientInvoice : bills

    Task ||--o{ TimeLog : tracks

    ClientInvoice ||--o{ ClientInvoiceItem : contains
    ClientInvoice ||--o{ ClientInvoicePayment : receives

    Freelancer {
        bigint id PK
        string name
        string slug
        enum status
    }

    Plan {
        decimal price_monthly
        int max_clients
        int max_projects
        boolean is_custom
    }

    Subscription {
        enum status
        enum billing_interval
        datetime read_only_at
    }

    Project {
        decimal hourly_rate
        string currency
        enum status
    }

    ClientInvoice {
        string invoice_number
        decimal total
        enum status
    }
```

---

## 8. Tenant isolation model

```mermaid
flowchart TB
    subgraph singleDB [Single PostgreSQL Database]
        subgraph tenantA [freelancer_id = 1]
            CA[clients]
            PA[projects]
            CIA[client_invoices]
        end

        subgraph tenantB [freelancer_id = 2]
            CB[clients]
            PB[projects]
            CIB[client_invoices]
        end

        subgraph shared [Platform-wide]
            Users[users]
            Plans[plans]
            Subs[subscriptions]
        end
    end

    TenantCtx[TenantContext.freelancer_id] -->|BelongsToFreelancer global scope| CA
    TenantCtx --> PA
    TenantCtx --> CIA

    TaskA[tasks] -->|via project.freelancer_id| PA
    TimeLogA[time_logs] -->|via task → project| PA
```

---

## 9. Technology stack (current + planned)

```mermaid
flowchart LR
    subgraph frontend [Frontend - planned]
        React[React / Next.js]
    end

    subgraph backend [Backend - current]
        Laravel[Laravel 13]
        Sanctum[Laravel Sanctum]
        Scramble[Scramble API Docs]
        Cashier[Laravel Cashier - Phase 15]
    end

    subgraph infra [Infrastructure]
        Neon[Neon PostgreSQL]
        Sail[Laravel Sail / Docker]
    end

    React --> Laravel
    Laravel --> Sanctum
    Laravel --> Scramble
    Laravel --> Cashier
    Laravel --> Neon
    Laravel --> Sail
    Cashier --> Stripe[Stripe]
```

---

## Diagram index

| # | Diagram | Use when |
|---|---------|----------|
| 0 | **Component communication** | How parts talk — request paths, webhooks, async |
| 1 | High-level system | Explaining overall stack to stakeholders |
| 2 | Actor / API map | Routing and permission design |
| 3 | Domain hierarchy | Understanding tenant data tree |
| 4 | Two billing layers | Separating platform vs client money |
| 5 | Request flow | Implementing middleware and policies |
| 6 | Onboarding lifecycle | Subscription and trial behaviour |
| 7 | Entity relationship | Database design and migrations |
| 8 | Tenant isolation | Multi-tenancy scoping strategy |
| 9 | Technology stack | Dev setup and dependencies |
