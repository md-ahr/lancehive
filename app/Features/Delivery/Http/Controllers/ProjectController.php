<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Pagination\Concerns\CursorPaginates;
use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Delivery\Http\Requests\DestroyProjectRequest;
use App\Features\Delivery\Http\Requests\IndexClientProjectRequest;
use App\Features\Delivery\Http\Requests\IndexProjectRequest;
use App\Features\Delivery\Http\Requests\ShowProjectRequest;
use App\Features\Delivery\Http\Requests\StoreProjectRequest;
use App\Features\Delivery\Http\Requests\UpdateProjectRequest;
use App\Features\Delivery\Http\Resources\ProjectResource;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\PlatformBilling\Services\PlanLimitService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Projects', weight: 31)]
final class ProjectController extends Controller
{
    use CursorPaginates;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
        private readonly TenantContext $tenantContext,
    ) {}

    #[Endpoint(title: 'List all tenant projects', description: 'Cursor-paginated projects for the active workspace.')]
    public function index(IndexProjectRequest $request): JsonResponse
    {
        $query = Project::query()->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = ProjectResource::collection($page['data'])->resolve();

        return response()->json($page);
    }

    #[Endpoint(title: 'List client projects', description: 'Cursor-paginated projects nested under a client.')]
    public function indexForClient(IndexClientProjectRequest $request, Client $client): JsonResponse
    {
        $query = Project::query()
            ->where('client_id', $client->id)
            ->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = ProjectResource::collection($page['data'])->resolve();

        return response()->json($page);
    }

    public function store(StoreProjectRequest $request, Client $client): JsonResponse
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $this->planLimitService->assertCanAddProject($freelancerId);

        $project = Project::query()->create([
            'client_id' => $client->id,
            'name' => $request->string('name')->toString(),
            'hourly_rate' => $request->validated('hourly_rate'),
            'currency' => $request->validated('currency') ?? 'BDT',
            'deadline' => $request->validated('deadline'),
            'status' => $request->validated('status') ?? ProjectStatus::Active,
        ]);

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShowProjectRequest $request, Project $project): ProjectResource
    {
        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $project->update($request->validated());

        return new ProjectResource($project->fresh());
    }

    public function destroy(DestroyProjectRequest $request, Project $project): MessageResource
    {
        $project->delete();

        return new MessageResource([
            'message' => 'Project deleted successfully.',
        ]);
    }
}
