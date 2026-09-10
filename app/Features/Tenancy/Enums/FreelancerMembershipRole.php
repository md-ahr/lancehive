<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Enums;

enum FreelancerMembershipRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
