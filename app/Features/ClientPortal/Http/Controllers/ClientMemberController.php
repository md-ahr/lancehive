<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Http\Controllers;

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\ClientPortal\Http\Requests\StoreClientMemberRequest;
use App\Features\ClientPortal\Http\Resources\ClientMembershipResource;
use App\Features\ClientPortal\Notifications\ClientPortalInviteNotification;
use App\Features\ClientPortal\Notifications\ClientPortalMemberAddedNotification;
use App\Features\ClientPortal\Services\ClientMemberInviteService;
use App\Features\Delivery\Models\Client;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Client Members', weight: 32)]
final class ClientMemberController extends Controller
{
    public function __construct(
        private readonly ClientMemberInviteService $inviteService,
    ) {}

    public function store(StoreClientMemberRequest $request, Client $client): JsonResponse
    {
        $validated = $request->validated();
        $email = strtolower($validated['email']);

        $result = $this->inviteService->invite($client, [
            'name' => $validated['name'],
            'email' => $email,
            'role' => ClientMembershipRole::from($validated['role']),
        ]);

        $user = $result->membership->user;

        if ($result->createdNewUser) {
            $this->sendNewUserInvite($client, $user);
        } else {
            $user->notify(new ClientPortalMemberAddedNotification($client));
        }

        return (new ClientMembershipResource($result->membership))
            ->response()
            ->setStatusCode(201);
    }

    private function sendNewUserInvite(Client $client, User $user): void
    {
        $token = Password::broker()->createToken($user);
        $resetUrl = config('app.frontend_url').'/reset-password'
            .'?token='.$token
            .'&email='.urlencode($user->email);

        $user->notify(new ClientPortalInviteNotification($client, $resetUrl));
    }
}
