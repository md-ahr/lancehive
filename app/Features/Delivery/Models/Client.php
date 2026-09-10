<?php

declare(strict_types=1);

namespace App\Features\Delivery\Models;

use App\Core\Tenancy\Concerns\BelongsToFreelancer;
use App\Features\ClientPortal\Models\ClientMembership;
use App\Features\Delivery\Enums\ClientStatus;
use App\Features\Tenancy\Models\Freelancer;
use Database\Factories\Delivery\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['freelancer_id', 'name', 'status', 'contact_email'])]
final class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToFreelancer, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
        ];
    }

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ClientMembership::class);
    }
}
