<?php

declare(strict_types=1);

namespace App\Features\Auth\Models;

use App\Features\Admin\Models\AdminActivityLog;
use App\Features\Auth\Enums\UserRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Settings\Casts\NotificationPreferencesCast;
use App\Features\Settings\Support\NotificationPreferences;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use App\Features\Tenancy\Models\Freelancer;
use App\Features\Tenancy\Models\FreelancerMembership;
use Database\Factories\Auth\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'timezone', 'locale', 'notification_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'notification_preferences' => NotificationPreferencesCast::class,
        ];
    }

    public function prefersNotification(string $key, ?Freelancer $freelancer = null): bool
    {
        if (! in_array($key, NotificationPreferences::keys(), true)) {
            return true;
        }

        if ($this->notification_preferences[$key] !== true) {
            return false;
        }

        if ($key === NotificationPreferences::SUBSCRIPTION_ALERTS) {
            return $freelancer !== null && $this->isOwnerOfFreelancer($freelancer);
        }

        if ($key === NotificationPreferences::INVOICE_ACTIVITY) {
            return $freelancer !== null && $this->canManageFreelancer($freelancer);
        }

        return true;
    }

    private function isOwnerOfFreelancer(Freelancer $freelancer): bool
    {
        return $freelancer->owner_user_id === $this->id
            || $this->freelancerMemberships()
                ->where('freelancer_id', $freelancer->id)
                ->where('role', FreelancerMembershipRole::Owner)
                ->exists();
    }

    private function canManageFreelancer(Freelancer $freelancer): bool
    {
        if ($this->isOwnerOfFreelancer($freelancer)) {
            return true;
        }

        return $this->freelancerMemberships()
            ->where('freelancer_id', $freelancer->id)
            ->where('role', FreelancerMembershipRole::Admin)
            ->exists();
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->isSuperAdmin() ?? false;
    }

    public function isUser(): bool
    {
        return $this->role?->isUser() ?? false;
    }

    /**
     * Whether the user belongs to at least one freelancer workspace.
     */
    public function isFreelancer(): bool
    {
        if ($this->relationLoaded('freelancerMemberships')) {
            return $this->freelancerMemberships->isNotEmpty();
        }

        return $this->freelancerMemberships()->exists();
    }

    /**
     * Whether the user belongs to at least one client portal organization.
     */
    public function isClient(): bool
    {
        if ($this->relationLoaded('clientMemberships')) {
            return $this->clientMemberships->isNotEmpty();
        }

        return $this->clientMemberships()->exists();
    }

    public function ownedFreelancer(): HasOne
    {
        return $this->hasOne(Freelancer::class, 'owner_user_id');
    }

    public function freelancerMemberships(): HasMany
    {
        return $this->hasMany(FreelancerMembership::class);
    }

    public function freelancers(): BelongsToMany
    {
        return $this->belongsToMany(Freelancer::class, 'freelancer_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function clientMemberships(): HasMany
    {
        return $this->hasMany(ClientMembership::class);
    }

    public function adminActivityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class, 'admin_user_id');
    }
}
