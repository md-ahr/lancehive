<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Concerns;

use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenantViaProject
{
    protected static function bootBelongsToTenantViaProject(): void
    {
        static::addGlobalScope('freelancer', function (Builder $builder): void {
            $freelancerId = app(TenantContext::class)->freelancerId();

            if ($freelancerId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            static::applyFreelancerScope($builder, $freelancerId);
        });
    }

    protected static function applyFreelancerScope(Builder $builder, int $freelancerId): void
    {
        $builder->whereHas(
            static::tenantScopeRelation(),
            static::tenantScopeConstraint($freelancerId),
        );
    }

    protected static function tenantScopeRelation(): string
    {
        return 'project';
    }

    protected static function tenantScopeConstraint(int $freelancerId): Closure
    {
        return fn (Builder $query): Builder => $query->where('freelancer_id', $freelancerId);
    }
}
