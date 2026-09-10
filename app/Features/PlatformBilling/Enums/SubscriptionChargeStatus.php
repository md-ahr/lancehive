<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Enums;

enum SubscriptionChargeStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
