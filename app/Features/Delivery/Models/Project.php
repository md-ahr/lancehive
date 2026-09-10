<?php

declare(strict_types=1);

namespace App\Features\Delivery\Models;

use App\Core\Tenancy\Concerns\BelongsToFreelancer;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\Delivery\Enums\ProjectStatus;
use App\Features\Tenancy\Models\Freelancer;
use Database\Factories\Delivery\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

#[Fillable([
    'client_id',
    'freelancer_id',
    'name',
    'hourly_rate',
    'currency',
    'status',
    'deadline',
])]
final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToFreelancer, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'status' => ProjectStatus::class,
            'deadline' => 'date',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (Project $project): void {
            if ($project->hourly_rate === null) {
                throw new InvalidArgumentException('hourly_rate is required when creating a project.');
            }
        });
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function clientInvoices(): HasMany
    {
        return $this->hasMany(ClientInvoice::class);
    }
}
