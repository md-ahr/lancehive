<?php

declare(strict_types=1);

namespace App\Features\Settings\Support;

final class NotificationPreferences
{
    public const string SUBSCRIPTION_ALERTS = 'subscription_alerts';

    public const string WORKSPACE_INVITES = 'workspace_invites';

    public const string INVOICE_ACTIVITY = 'invoice_activity';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::SUBSCRIPTION_ALERTS,
            self::WORKSPACE_INVITES,
            self::INVOICE_ACTIVITY,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            self::SUBSCRIPTION_ALERTS => true,
            self::WORKSPACE_INVITES => true,
            self::INVOICE_ACTIVITY => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, bool>
     */
    public static function normalize(array $preferences): array
    {
        $normalized = self::defaults();

        foreach (self::keys() as $key) {
            if (array_key_exists($key, $preferences)) {
                $normalized[$key] = (bool) $preferences[$key];
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, bool>  $current
     * @param  array<string, mixed>  $partial
     * @return array<string, bool>
     */
    public static function merge(array $current, array $partial): array
    {
        return self::normalize([...$current, ...$partial]);
    }
}
