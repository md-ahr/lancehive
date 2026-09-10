<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Enums;

enum SubscriptionProvider: string
{
    case Stripe = 'stripe';
    case Manual = 'manual';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
