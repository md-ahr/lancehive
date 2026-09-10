<?php

declare(strict_types=1);

namespace App\Features\Settings\Casts;

use App\Features\Settings\Support\NotificationPreferences;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<array<string, bool>, array<string, bool>|string|null>
 */
final class NotificationPreferencesCast implements CastsAttributes
{
    /**
     * @return array<string, bool>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return NotificationPreferences::defaults();
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded)
                ? NotificationPreferences::normalize($decoded)
                : NotificationPreferences::defaults();
        }

        return is_array($value)
            ? NotificationPreferences::normalize($value)
            : NotificationPreferences::defaults();
    }

    /**
     * @param  array<string, bool>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => json_encode(NotificationPreferences::defaults())];
        }

        $normalized = is_array($value)
            ? NotificationPreferences::normalize($value)
            : NotificationPreferences::defaults();

        return [$key => json_encode($normalized)];
    }
}
