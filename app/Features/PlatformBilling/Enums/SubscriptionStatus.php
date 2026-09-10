<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case ReadOnly = 'read_only';
    case Canceled = 'canceled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canWrite(): bool
    {
        return in_array($this, [self::Trialing, self::Active, self::PastDue], true);
    }
}
