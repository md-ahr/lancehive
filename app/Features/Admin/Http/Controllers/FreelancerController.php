<?php

declare(strict_types=1);

namespace App\Features\Admin\Http\Controllers;

use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Pagination\Concerns\CursorPaginates;
use App\Features\Admin\Http\Requests\IndexFreelancerRequest;
use App\Features\Admin\Http\Requests\StoreFreelancerRequest;
use App\Features\Admin\Http\Requests\UpdateFreelancerStatusRequest;
use App\Features\Admin\Notifications\FreelancerInviteNotification;
use App\Features\Admin\Services\AdminActivityLogger;
use App\Features\Admin\Services\FreelancerOnboardingService;
use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerStatus;
use App\Features\Tenancy\Http\Resources\FreelancerDetailResource;
use App\Features\Tenancy\Http\Resources\FreelancerResource;
use App\Features\Tenancy\Models\Freelancer;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

#[Group('Admin', weight: 10)]
final class FreelancerController extends Controller
{
    use CursorPaginates;

    public function __construct(
        private readonly FreelancerOnboardingService $onboardingService,
        private readonly AdminActivityLogger $activityLogger,
    ) {}

    public function index(IndexFreelancerRequest $request): JsonResponse
    {
        $query = Freelancer::query()->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = FreelancerResource::collection($page['data'])->resolve();

        return response()->json($page);
    }

    public function show(Request $request, Freelancer $freelancer): FreelancerDetailResource
    {
        if ($request->filled('freelancer_id')) {
            $this->activityLogger->log(
                $request->user(),
                'freelancer.override',
                $freelancer,
                ['freelancer_id' => (int) $request->query('freelancer_id')],
                $request,
            );
        }

        $freelancer->load(['owner', 'subscription.plan']);
        $freelancer->loadCount('memberships');

        return new FreelancerDetailResource($freelancer);
    }

    public function store(StoreFreelancerRequest $request): JsonResponse
    {
        $freelancer = $this->onboardingService->onboard($request->validated());

        $this->activityLogger->log(
            $request->user(),
            'freelancer.onboard',
            $freelancer,
            [
                'workspace_slug' => $freelancer->slug,
                'owner_email' => $freelancer->owner?->email,
                'plan' => $freelancer->subscription?->plan?->slug,
            ],
            $request,
        );

        $this->sendInvite($freelancer);

        $freelancer->loadCount('memberships');

        return (new FreelancerDetailResource($freelancer))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateFreelancerStatusRequest $request, Freelancer $freelancer): FreelancerResource
    {
        $status = FreelancerStatus::from($request->string('status')->toString());

        $freelancer->update(['status' => $status]);

        if ($status === FreelancerStatus::Suspended) {
            $this->activityLogger->log(
                $request->user(),
                'freelancer.suspend',
                $freelancer,
                ['status' => $status->value],
                $request,
            );
        }

        return new FreelancerResource($freelancer->fresh());
    }

    public function resendInvite(Request $request, Freelancer $freelancer): MessageResource
    {
        $freelancer->load('owner');

        if ($freelancer->owner === null) {
            throw new ApiException(ApiErrorCode::NotFound);
        }

        if ($freelancer->status === FreelancerStatus::Active && $freelancer->owner->email_verified_at !== null) {
            throw new ApiException(
                ApiErrorCode::Forbidden,
                'This workspace owner has already accepted the invitation.',
            );
        }

        $this->sendInvite($freelancer);

        return new MessageResource([
            'message' => 'Invitation resent successfully.',
        ]);
    }

    private function sendInvite(Freelancer $freelancer): void
    {
        $owner = $freelancer->owner;

        if (! $owner instanceof User) {
            return;
        }

        $token = Password::broker()->createToken($owner);
        $resetUrl = config('app.frontend_url').'/reset-password'
            .'?token='.$token
            .'&email='.urlencode($owner->email);

        $owner->notify(new FreelancerInviteNotification($freelancer, $resetUrl));
    }
}
