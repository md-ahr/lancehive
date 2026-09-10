<?php

declare(strict_types=1);

namespace App\Features\Reporting\Models;

use App\Features\Auth\Models\User;
use App\Features\Reporting\Enums\ExportFormat;
use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Tenancy\Models\Freelancer;
use Database\Factories\Reporting\ReportExportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'freelancer_id',
    'saved_report_id',
    'requested_by_user_id',
    'report_type',
    'filters',
    'format',
    'status',
    'file_path',
    'row_count',
    'error_message',
    'expires_at',
    'completed_at',
])]
final class ReportExport extends Model
{
    /** @use HasFactory<ReportExportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_type' => ReportType::class,
            'filters' => 'array',
            'format' => ExportFormat::class,
            'status' => ExportStatus::class,
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ReportExportFactory
    {
        return ReportExportFactory::new();
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

    public function savedReport(): BelongsTo
    {
        return $this->belongsTo(SavedReport::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPlatformScoped(): bool
    {
        return $this->freelancer_id === null;
    }
}
