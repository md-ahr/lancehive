<?php

declare(strict_types=1);

namespace App\Features\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class UserCollectionResource extends ResourceCollection
{
    public $collects = UserResource::class;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'users' => $this->collection,
        ];
    }
}
