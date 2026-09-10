<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Models;

use App\Features\PlatformBilling\Enums\BillingInterval;
use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\Tenancy\Models\Freelancer;
use Database\Factories\PlatformBilling\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'freelancer_id',
    'plan_id',
    'status',
    'billing_interval',
    'trial_ends_at',
    'current_period_start',
    'current_period_end',
    'read_only_at',
    'canceled_at',
    'provider',
    'provider_subscription_id',
])]
final class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'billing_interval' => BillingInterval::class,
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'read_only_at' => 'datetime',
            'canceled_at' => 'datetime',
            'provider' => SubscriptionProvider::class,
        ];
    }

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(SubscriptionCharge::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function onTrial(): bool
    {
        return $this->status === SubscriptionStatus::Trialing;
    }

    public function isPastDue(): bool
    {
        return $this->status === SubscriptionStatus::PastDue;
    }

    public function isReadOnly(): bool
    {
        return $this->status === SubscriptionStatus::ReadOnly;
    }

    public function canWrite(): bool
    {
        return $this->status?->canWrite() ?? false;
    }
}
