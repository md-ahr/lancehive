<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Http\Controllers;

use App\Core\ClientPortal\ClientContext;
use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Pagination\Concerns\CursorPaginates;
use App\Features\ClientPortal\Http\Requests\IndexPortalProjectRequest;
use App\Features\Delivery\Http\Resources\ProjectResource;
use App\Features\Delivery\Models\Project;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;

#[HeaderParameter('X-Client-Id', description: 'Active client organization ID', required: true)]
#[Group('Client Portal', weight: 33)]
final class PortalProjectController extends Controller
{
    use CursorPaginates;

    public function __construct(private readonly ClientContext $clientContext) {}

    public function index(IndexPortalProjectRequest $request): JsonResponse
    {
        $clientId = $this->clientContext->clientId();

        if ($clientId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active client organization is required.');
        }

        $query = Project::query()
            ->withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->orderBy('id');

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = ProjectResource::collection($page['data'])->resolve();

        return response()->json($page);
    }
}
