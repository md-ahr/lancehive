<?php

declare(strict_types=1);

namespace App\Features\Auth\Models;

use App\Features\Admin\Models\AdminActivityLog;
use App\Features\Auth\Enums\UserRole;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Models\TimeLog;
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

#[Fillable(['name', 'email', 'password', 'role'])]
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
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->isSuperAdmin() ?? false;
    }

    public function isFreelancer(): bool
    {
        return $this->role?->isFreelancer() ?? false;
    }

    public function isClient(): bool
    {
        return $this->role?->isClient() ?? false;
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
