---
name: lancehive-api-docs
description: Use when adding or changing API endpoints. Ensures Scramble OpenAPI docs at /docs/api are complete — Group attributes, Form Requests, API Resources, tenant headers, and DocumentationTest path assertions.
---

# LanceHive API Docs (Scramble)

Contract markdown: `docs/api/` via **`lancehive-api-contract`**. Auth/errors: **`lancehive-guardrails`**. Rule: `api-scramble-docs.mdc`.

## File placement

| Artifact | Path |
|----------|------|
| Route definition | `routes/features/v1/{feature}.php` |
| Controller, Request, Resource | `app/Features/{Feature}/Http/...` (no `V1/` subfolder) |

## Checklist (every endpoint)

- [ ] Route file `routes/features/v1/{feature}.php`, required in `routes/api.php` via `config('api.features_routes')` → `/api/v1/...`
- [ ] Controller has `#[Group('Feature', weight: N)]` — see `api-scramble-docs.mdc` weights
- [ ] Form Request for input (`StoreXRequest`, `IndexXRequest` for query params)
- [ ] Return type is `XResource`, `AnonymousResourceCollection`, or typed `JsonResponse` with Resource
- [ ] Tenant routes: `#[HeaderParameter('X-Freelancer-Id', …, required: true)]` on controller
- [ ] List routes: document `per_page` (1–100) and `cursor` in index Form Request
- [ ] `#[Endpoint(title:, description:)]` when behavior is non-obvious
- [ ] Update `docs/api/endpoints/{group}.md` (request, response, Errors table)
- [ ] Test: assert path exists in OpenAPI spec

## Controller pattern

```php
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Clients', weight: 30)]
final class ClientController extends Controller
{
    public function store(StoreClientRequest $request, CreateClientAction $action): ClientResource
    {
        return new ClientResource($action->execute($request->validated()));
    }

    public function index(IndexClientRequest $request): AnonymousResourceCollection
    {
        // cursorPaginate — query rules inferred from IndexClientRequest
    }
}
```

## API Resource (response schema)

```php
final class ClientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status, // backed enum → documented automatically
        ];
    }
}
```

## Doc test (add path per feature)

```php
public function test_openapi_spec_documents_client_routes(): void
{
    $paths = $this->getJson('/docs/api.json')->json('paths');

    $this->assertArrayHasKey('/clients', $paths);
    $this->assertArrayHasKey('/clients/{client}', $paths);
}
```

Run: `vendor/bin/sail artisan test --compact tests/Feature/Api/DocumentationTest.php`

## Existing reference

- `app/Features/Auth/Http/Controllers/AuthController.php` — `#[Group('Authentication')]`
- `tests/Feature/Api/DocumentationTest.php` — spec accessibility + path assertions
- `config/scramble.php` — Sanctum bearer security auto-detection

## Do not

Migrate Auth endpoints to Resources in the same task unless asked — **new** endpoints must use Resources from day one.
