<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Services;

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Cache\ClientMembershipCache;
use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ClientMemberInviteService
{
    public function __construct(private readonly ClientMembershipCache $membershipCache) {}

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     role: ClientMembershipRole
     * }  $data
     */
    public function invite(Client $client, array $data): ClientMemberInviteResult
    {
        return DB::transaction(function () use ($client, $data): ClientMemberInviteResult {
            $email = Str::lower($data['email']);
            $user = User::query()->where('email', $email)->first();
            $createdNewUser = false;

            if ($user !== null) {
                $existingMembership = ClientMembership::query()
                    ->where('client_id', $client->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($existingMembership) {
                    throw ValidationException::withMessages([
                        'email' => ['This user is already a member of the client organization.'],
                    ]);
                }
            } else {
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => Str::password(),
                    'role' => UserRole::User,
                ]);
                $createdNewUser = true;
            }

            $membership = ClientMembership::query()->create([
                'client_id' => $client->id,
                'user_id' => $user->id,
                'role' => $data['role'],
            ]);

            $this->membershipCache->forget($user->id);

            return new ClientMemberInviteResult($membership->load('user'), $createdNewUser);
        });
    }
}
