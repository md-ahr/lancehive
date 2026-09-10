<?php

declare(strict_types=1);

namespace App\Features\Reporting\Models;

use App\Features\Auth\Models\User;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Tenancy\Models\Freelancer;
use Database\Factories\Reporting\SavedReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'freelancer_id',
    'created_by_user_id',
    'name',
    'report_type',
    'filters',
])]
final class SavedReport extends Model
{
    /** @use HasFactory<SavedReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_type' => ReportType::class,
            'filters' => 'array',
        ];
    }

    protected static function newFactory(): SavedReportFactory
    {
        return SavedReportFactory::new();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForWorkspace(Builder $query, int $freelancerId): Builder
    {
        return $query->where('freelancer_id', $freelancerId);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForPlatform(Builder $query): Builder
    {
        return $query->whereNull('freelancer_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }

    public function isPlatformScoped(): bool
    {
        return $this->freelancer_id === null;
    }
}
