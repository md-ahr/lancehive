<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Enums;

enum FreelancerStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
