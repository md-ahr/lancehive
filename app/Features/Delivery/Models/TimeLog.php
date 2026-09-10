<?php

declare(strict_types=1);

namespace App\Features\Delivery\Models;

use App\Core\Tenancy\Concerns\BelongsToTenantViaProject;
use App\Features\Auth\Models\User;
use App\Features\ClientBilling\Models\ClientInvoiceItem;
use Closure;
use Database\Factories\Delivery\TimeLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'user_id',
    'hours',
    'description',
    'logged_at',
    'client_invoice_item_id',
])]
final class TimeLog extends Model
{
    /** @use HasFactory<TimeLogFactory> */
    use BelongsToTenantViaProject, HasFactory;

    protected static function tenantScopeRelation(): string
    {
        return 'task';
    }

    protected static function tenantScopeConstraint(int $freelancerId): Closure
    {
        return fn (Builder $query): Builder => $query->whereHas(
            'project',
            fn (Builder $projectQuery): Builder => $projectQuery->where('freelancer_id', $freelancerId),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'logged_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TimeLogFactory
    {
        return TimeLogFactory::new();
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clientInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(ClientInvoiceItem::class);
    }
}
