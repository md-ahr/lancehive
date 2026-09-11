<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class RootController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'name' => config('app.name'),
            'api' => '/'.config('api.prefix'),
            'docs' => '/docs/api',
            'health' => '/up',
        ]);
    }
}
