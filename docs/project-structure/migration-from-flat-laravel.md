# Migration Guide — Flat Laravel → Feature-Based Layout

This guide moves the existing LanceHive codebase from the default Laravel structure to the [feature-based layout](./feature-based-architecture.md) without a big-bang rewrite.

---

## When to migrate

| Phase | Structure |
|-------|-----------|
| Phase 0–1 (now) | Flat `app/Http`, `app/Models` is fine |
| **Phase 2+ (Tenancy API)** | Create `app/Core/` and `app/Features/`; migrate Auth first |
| Each new domain entity | Add under the owning feature from day one |

Do **not** migrate everything upfront. Move code when you touch it or when starting a new feature module.

---

## Step 1 — Create skeleton folders

```bash
mkdir -p app/Core/{Http/Middleware,Tenancy,Cache,Pagination,Support}
mkdir -p app/Features/Auth/{Actions,Http/Controllers,Http/Requests,Models,Policies}
mkdir -p routes/features/v1
mkdir -p tests/Feature/Auth
```

Repeat `app/Features/{Name}/...` when starting Tenancy, Delivery, etc.

---

## Step 2 — Migrate Auth (first feature)

| From | To |
|------|-----|
| `app/Http/Controllers/Api/AuthController.php` | `app/Features/Auth/Http/Controllers/AuthController.php` |
| `app/Http/Controllers/Api/UserController.php` | `app/Features/Auth/Http/Controllers/UserController.php` |
| `app/Http/Requests/Auth/*` | `app/Features/Auth/Http/Requests/*` |
| `app/Models/User.php` | `app/Features/Auth/Models/User.php` |
| `app/Enums/UserRole.php` | `app/Features/Auth/Enums/UserRole.php` |

Update namespaces:

```php
// Before
namespace App\Http\Controllers\Api;

// After
namespace App\Features\Auth\Http\Controllers;
```

Update imports across the codebase (tests, factories, seeders, providers).

**Routes:** move auth routes to `routes/features/v1/auth.php` and require from `routes/api.php` via `config('api.features_routes')` inside `Route::prefix(config('api.route_version'))` (`/api/v1`).

**Tests:** move to `tests/Feature/Auth/` and update class namespaces if needed.

Run:

```bash
php artisan test
composer dump-autoload
```

---

## Step 3 — Add Core tenancy (Phase 2)

When implementing Task 2.1–2.10, add:

```
app/Core/Tenancy/TenantContext.php
app/Core/Tenancy/BelongsToFreelancer.php
app/Core/Http/Middleware/EnsureFreelancerContext.php
app/Core/Http/Middleware/EnsureWritableSubscription.php
```

Register middleware aliases in `bootstrap/app.php`.

Create feature:

```
app/Features/Tenancy/
├── Models/Freelancer.php
├── Models/FreelancerMembership.php
├── Enums/...
├── Http/Controllers/FreelancerController.php
└── Policies/FreelancerPolicy.php
```

---

## Step 4 — Composer autoload (optional explicit paths)

PSR-4 `App\\` → `app/` already covers `App\Features\*` and `App\Core\*`. No `composer.json` change required if folders match namespaces.

If you add a `app/Features/Auth/Models/User.php`, update factory:

```php
// database/factories/Auth/UserFactory.php
namespace Database\Factories\Auth;

use App\Features\Auth\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;
    // ...
}
```

Register in the model:

```php
protected static function newFactory(): UserFactory
{
    return UserFactory::new();
}
```

---

## Step 5 — Incremental rules for existing files

1. **New code** → always under `Features/` or `Core/`.
2. **Editing old flat file** → move to feature folder in the same PR if the change is non-trivial.
3. **Delete empty legacy folders** only when no references remain (`app/Http/Controllers/Api/` after Auth migration).

---

## Step 6 — Verify nothing broke

```bash
php artisan route:list
php artisan test
./vendor/bin/pint
```

Check Scramble/OpenAPI still documents moved controllers (`config/scramble.php` may need path hints if using custom discovery).

---

## Namespace cheat sheet

| Path | Namespace |
|------|-----------|
| `app/Core/Tenancy/TenantContext.php` | `App\Core\Tenancy` |
| `app/Features/Delivery/Models/Client.php` | `App\Features\Delivery\Models` |
| `app/Features/ClientBilling/Services/ClientInvoiceService.php` | `App\Features\ClientBilling\Services` |
| `database/factories/Delivery/ClientFactory.php` | `Database\Factories\Delivery` |
| `tests/Feature/Delivery/ClientCrudTest.php` | `Tests\Feature\Delivery` |

---

## Rollback

Git revert the migration PR. Keep flat structure until Phase 2 if migration causes friction — the target layout is a **convention**, not a runtime requirement. Consistency matters most once multiple developers or features land.
