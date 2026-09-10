<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Models;

use Database\Factories\PlatformBilling\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'price_monthly',
    'price_yearly',
    'currency',
    'max_clients',
    'max_projects',
    'max_team_members',
    'is_custom',
    'is_active',
    'sort_order',
    'stripe_price_monthly_id',
    'stripe_price_yearly_id',
])]
final class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_clients' => 'integer',
            'max_projects' => 'integer',
            'max_team_members' => 'integer',
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): PlanFactory
    {
        return PlanFactory::new();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
