<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Enums;

enum FreelancerMembershipRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canManageInvoices(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canManageAllTimeLogs(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canManageClientsAndProjects(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canManageTeam(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canRemoveMember(self $targetRole): bool
    {
        if ($this === self::Owner) {
            return $targetRole !== self::Owner;
        }

        if ($this === self::Admin) {
            return $targetRole === self::Member;
        }

        return false;
    }

    public function isInvitable(): bool
    {
        return in_array($this, [self::Admin, self::Member], true);
    }
}
