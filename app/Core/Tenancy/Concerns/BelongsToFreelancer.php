<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Concerns;

use App\Core\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToFreelancer
{
    protected static function bootBelongsToFreelancer(): void
    {
        static::addGlobalScope('freelancer', function (Builder $builder): void {
            $freelancerId = app(TenantContext::class)->freelancerId();

            if ($freelancerId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($builder->getModel()->getTable().'.freelancer_id', $freelancerId);
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('freelancer_id') !== null) {
                return;
            }

            $freelancerId = app(TenantContext::class)->freelancerId();

            if ($freelancerId !== null) {
                $model->setAttribute('freelancer_id', $freelancerId);
            }
        });
    }
}
