<?php

declare(strict_types=1);

namespace App\Features\Admin\Services;

use App\Features\Admin\Models\AdminActivityLog;
use App\Features\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AdminActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        User $admin,
        string $action,
        ?Model $target = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): AdminActivityLog {
        return AdminActivityLog::query()->create([
            'admin_user_id' => $admin->id,
            'action' => $action,
            'target_type' => $target !== null ? $target->getMorphClass() : null,
            'target_id' => $target?->getKey(),
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
        ]);
    }
}
