<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Models;

use App\Core\Tenancy\TenantContext;
use App\Features\Auth\Models\User;
use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use Database\Factories\Tenancy\FreelancerMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['freelancer_id', 'user_id', 'role'])]
final class FreelancerMembership extends Model
{
    /** @use HasFactory<FreelancerMembershipFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => FreelancerMembershipRole::class,
        ];
    }

    protected static function newFactory(): FreelancerMembershipFactory
    {
        return FreelancerMembershipFactory::new();
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope route binding to the active tenant workspace.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $freelancerId = app(TenantContext::class)->freelancerId();

        if ($freelancerId === null) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('freelancer_id', $freelancerId)
            ->first();
    }
}
