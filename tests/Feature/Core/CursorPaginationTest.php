<?php

declare(strict_types=1);

use App\Core\Pagination\Concerns\CursorPaginates;
use App\Features\Delivery\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Route::middleware(['api', 'auth:sanctum', 'freelancer.context'])
        ->get($this->apiUrl('__test/clients'), function (Request $request) {
            $controller = new class
            {
                use CursorPaginates;

                public function __invoke(Request $request): array
                {
                    return $this->cursorPaginate(Client::query()->orderBy('id'), $request);
                }
            };

            return $controller($request);
        });
});

it('returns cursor pagination meta for tenant list queries', function () {
    $workspace = $this->createTenantWorkspace();
    Client::factory()->count(3)->for($workspace['freelancer'])->create();

    Sanctum::actingAs($workspace['user']);

    $this->withHeader('X-Freelancer-Id', (string) $workspace['freelancer']->id)
        ->getJson($this->apiUrl('__test/clients?per_page=2'))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ])
        ->assertJsonPath('meta.per_page', 2);
});

it('rejects per_page values above 100', function () {
    $workspace = $this->createTenantWorkspace();

    Sanctum::actingAs($workspace['user']);

    $this->withHeader('X-Freelancer-Id', (string) $workspace['freelancer']->id)
        ->getJson($this->apiUrl('__test/clients?per_page=101'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});
