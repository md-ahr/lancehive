<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Models;

use App\Features\Auth\Models\User;
use App\Features\ClientPortal\Enums\ClientMembershipRole;
use App\Features\Delivery\Models\Client;
use Database\Factories\ClientPortal\ClientMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'user_id', 'role'])]
final class ClientMembership extends Model
{
    /** @use HasFactory<ClientMembershipFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ClientMembershipRole::class,
        ];
    }

    protected static function newFactory(): ClientMembershipFactory
    {
        return ClientMembershipFactory::new();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
