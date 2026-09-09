<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Freelancer = 'freelancer';
    case Client = 'client';

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isFreelancer(): bool
    {
        return $this === self::Freelancer;
    }

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
