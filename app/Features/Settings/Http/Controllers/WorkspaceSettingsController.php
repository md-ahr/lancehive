<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Tenancy\TenantContext;
use App\Features\Settings\Http\Requests\ShowWorkspaceSettingsRequest;
use App\Features\Settings\Http\Requests\UpdateWorkspaceSettingsRequest;
use App\Features\Settings\Http\Resources\WorkspaceSettingsResource;
use App\Features\Settings\Services\WorkspaceSettingsService;
use App\Features\Tenancy\Models\Freelancer;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Settings', weight: 15)]
final class WorkspaceSettingsController extends Controller
{
    public function __construct(
        private readonly WorkspaceSettingsService $workspaceSettingsService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function show(ShowWorkspaceSettingsRequest $request): WorkspaceSettingsResource
    {
        return new WorkspaceSettingsResource(
            $this->workspaceSettingsService->get($this->resolveFreelancer()),
        );
    }

    public function update(UpdateWorkspaceSettingsRequest $request): WorkspaceSettingsResource
    {
        $freelancer = $this->resolveFreelancer();
        $updated = $this->workspaceSettingsService->update($freelancer, $request->validated());

        return new WorkspaceSettingsResource($this->workspaceSettingsService->get($updated));
    }

    private function resolveFreelancer(): Freelancer
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        return Freelancer::query()->findOrFail($freelancerId);
    }
}
