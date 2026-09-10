<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Services;

use App\Features\ClientPortal\Models\ClientMembership;

final readonly class ClientMemberInviteResult
{
    public function __construct(
        public ClientMembership $membership,
        public bool $createdNewUser,
    ) {}
}
