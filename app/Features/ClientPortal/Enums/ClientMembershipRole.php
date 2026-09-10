<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Enums;

enum ClientMembershipRole: string
{
    case Primary = 'primary';
    case Member = 'member';
    case Viewer = 'viewer';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
