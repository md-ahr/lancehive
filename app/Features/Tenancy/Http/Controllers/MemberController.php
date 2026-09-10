<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Pagination\Concerns\CursorPaginates;
use App\Core\Tenancy\TenantContext;
use App\Features\Admin\Notifications\FreelancerInviteNotification;
use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Cache\MembershipCache;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Http\Requests\DestroyMemberRequest;
use App\Features\Tenancy\Http\Requests\IndexMemberRequest;
use App\Features\Tenancy\Http\Requests\StoreMemberRequest;
use App\Features\Tenancy\Http\Resources\FreelancerMembershipResource;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use App\Features\Tenancy\Notifications\WorkspaceMemberAddedNotification;
use App\Features\Tenancy\Services\TeamMemberInviteService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Members', weight: 25)]
final class MemberController extends Controller
{
    use CursorPaginates;

    public function __construct(
        private readonly TeamMemberInviteService $inviteService,
        private readonly MembershipCache $membershipCache,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(IndexMemberRequest $request): JsonResponse
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $query = FreelancerMembership::query()
            ->where('freelancer_id', $freelancerId)
            ->with('user')
            ->orderBy('id');

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = FreelancerMembershipResource::collection($page['data'])->resolve();

        return response()->json($page);
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $freelancerId = $this->tenantContext->freelancerId();

        if ($freelancerId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active freelancer workspace is required.');
        }

        $validated = $request->validated();
        $email = strtolower($validated['email']);
        $existingUser = User::query()->where('email', $email)->exists();

        $membership = $this->inviteService->invite($freelancerId, [
            'name' => $validated['name'],
            'email' => $email,
            'role' => FreelancerMembershipRole::from($validated['role']),
        ]);

        $freelancer = Freelancer::query()->findOrFail($freelancerId);
        $user = $membership->user;

        if ($existingUser) {
            $user->notify(new WorkspaceMemberAddedNotification($freelancer));
        } else {
            $this->sendNewUserInvite($freelancer, $user);
        }

        return (new FreelancerMembershipResource($membership))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(DestroyMemberRequest $request, FreelancerMembership $membership): MessageResource
    {
        $userId = $membership->user_id;
        $membership->delete();
        $this->membershipCache->forget($userId);

        return new MessageResource([
            'message' => 'Workspace member removed successfully.',
        ]);
    }

    private function sendNewUserInvite(Freelancer $freelancer, User $user): void
    {
        $token = Password::broker()->createToken($user);
        $resetUrl = config('app.frontend_url').'/reset-password'
            .'?token='.$token
            .'&email='.urlencode($user->email);

        $user->notify(new FreelancerInviteNotification($freelancer, $resetUrl));
    }
}
