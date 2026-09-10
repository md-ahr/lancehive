<?php

declare(strict_types=1);

namespace App\Features\Auth\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case User = 'user';

    /** @deprecated Use membership roles on pivot tables instead */
    case Freelancer = 'freelancer';

    /** @deprecated Use client_memberships pivot roles instead */
    case Client = 'client';

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isUser(): bool
    {
        return $this === self::User;
    }

    /**
     * @deprecated Prefer User::isFreelancer() which checks freelancer_memberships.
     */
    public function isFreelancer(): bool
    {
        return $this === self::Freelancer;
    }

    /**
     * @deprecated Prefer User::isClient() which checks client_memberships.
     */
    public function isClient(): bool
    {
        return $this === self::Client;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
