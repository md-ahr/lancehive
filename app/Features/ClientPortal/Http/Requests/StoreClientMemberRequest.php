<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Http\Requests;

use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreClientMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        if (! $client instanceof Client) {
            return false;
        }

        return $this->user()?->can('create', [ClientMembership::class, $client]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => [
                'required',
                'string',
                Rule::in(array_map(
                    fn (ClientMembershipRole $role): string => $role->value,
                    array_filter(
                        ClientMembershipRole::cases(),
                        fn (ClientMembershipRole $role): bool => $role->isInvitable(),
                    ),
                )),
            ],
        ];
    }
}
