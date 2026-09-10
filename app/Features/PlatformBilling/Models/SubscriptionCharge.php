<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Models;

use App\Features\PlatformBilling\Enums\SubscriptionChargeStatus;
use Database\Factories\PlatformBilling\SubscriptionChargeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'subscription_id',
    'amount',
    'currency',
    'status',
    'paid_at',
    'provider_charge_id',
])]
final class SubscriptionCharge extends Model
{
    /** @use HasFactory<SubscriptionChargeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => SubscriptionChargeStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SubscriptionChargeFactory
    {
        return SubscriptionChargeFactory::new();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
