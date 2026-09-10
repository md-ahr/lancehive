<?php

declare(strict_types=1);

namespace App\Features\Delivery\Enums;

enum ClientStatus: string
{
    case Active = 'active';
    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
