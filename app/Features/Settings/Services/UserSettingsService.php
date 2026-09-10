<?php

declare(strict_types=1);

namespace App\Features\Settings\Services;

use App\Features\Auth\Models\User;
use App\Features\Settings\Support\NotificationPreferences;
use Illuminate\Validation\ValidationException;

final class UserSettingsService
{
    /**
     * @return array{timezone: string, locale: string, notification_preferences: array<string, bool>}
     */
    public function get(User $user): array
    {
        return [
            'timezone' => $user->timezone,
            'locale' => $user->locale,
            'notification_preferences' => $user->notification_preferences,
        ];
    }

    /**
     * @param  array{timezone?: string, locale?: string, notification_preferences?: array<string, mixed>}  $data
     */
    public function update(User $user, array $data): User
    {
        $updates = [];

        if (array_key_exists('timezone', $data)) {
            $this->assertValidTimezone($data['timezone']);
            $updates['timezone'] = $data['timezone'];
        }

        if (array_key_exists('locale', $data)) {
            $this->assertValidLocale($data['locale']);
            $updates['locale'] = $data['locale'];
        }

        if (array_key_exists('notification_preferences', $data)) {
            $updates['notification_preferences'] = NotificationPreferences::merge(
                $user->notification_preferences,
                $data['notification_preferences'],
            );
        }

        if ($updates !== []) {
            $user->update($updates);
        }

        return $user->fresh();
    }

    private function assertValidTimezone(string $timezone): void
    {
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw ValidationException::withMessages([
                'timezone' => ['The timezone must be a valid IANA timezone identifier.'],
            ]);
        }
    }

    private function assertValidLocale(string $locale): void
    {
        if ($locale !== 'en') {
            throw ValidationException::withMessages([
                'locale' => ['The selected locale is invalid.'],
            ]);
        }
    }
}
