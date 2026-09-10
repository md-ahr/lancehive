<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Pagination\Concerns\CursorPaginates;
use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Delivery\Http\Requests\DestroyClientRequest;
use App\Features\Delivery\Http\Requests\IndexClientRequest;
use App\Features\Delivery\Http\Requests\ShowClientRequest;
use App\Features\Delivery\Http\Requests\StoreClientRequest;
use App\Features\Delivery\Http\Requests\UpdateClientRequest;
use App\Features\Delivery\Http\Resources\ClientResource;
use App\Features\Delivery\Models\Client;
use App\Features\PlatformBilling\Services\PlanLimitService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Clients', weight: 30)]
final class ClientController extends Controller
{
    use CursorPaginates;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(IndexClientRequest $request): JsonResponse
    {
        $query = Client::query()->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = ClientResource::collection($page['data'])->resolve();

        return response()->json($page);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $this->planLimitService->assertCanAddClient($freelancerId);

        $client = Client::query()->create([
            'name' => $request->string('name')->toString(),
            'contact_email' => $request->validated('contact_email'),
            'status' => ClientStatus::Active,
        ]);

        return (new ClientResource($client))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShowClientRequest $request, Client $client): ClientResource
    {
        return new ClientResource($client);
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        $client->update($request->validated());

        return new ClientResource($client->fresh());
    }

    public function destroy(DestroyClientRequest $request, Client $client): MessageResource
    {
        if ($client->status !== ClientStatus::Archived) {
            $client->update(['status' => ClientStatus::Archived]);
        }

        return new MessageResource([
            'message' => 'Client archived successfully.',
        ]);
    }
}
