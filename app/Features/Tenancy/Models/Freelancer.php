<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Models;

use App\Features\Auth\Models\User;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Models\Client;
use App\Features\Delivery\Models\Project;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\Tenancy\Enums\FreelancerStatus;
use Database\Factories\Tenancy\FreelancerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;

#[Fillable([
    'name',
    'slug',
    'status',
    'owner_user_id',
    'default_currency',
    'invoice_number_prefix',
    'default_tax_rate',
    'invoice_footer_notes',
    'business_name',
    'business_email',
    'business_address',
])]
final class Freelancer extends Model
{
    /** @use HasFactory<FreelancerFactory> */
    use Billable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FreelancerStatus::class,
            'default_tax_rate' => 'decimal:2',
        ];
    }

    protected static function newFactory(): FreelancerFactory
    {
        return FreelancerFactory::new();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(FreelancerMembership::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'freelancer_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function clientInvoices(): HasMany
    {
        return $this->hasMany(ClientInvoice::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }
}
