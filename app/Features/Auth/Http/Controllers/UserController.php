<?php

declare(strict_types=1);

namespace App\Features\Auth\Http\Controllers;

use App\Core\Pagination\Concerns\CursorPaginates;
use App\Features\Auth\Http\Requests\ListUsersRequest;
use App\Features\Auth\Http\Resources\UserResource;
use App\Features\Auth\Models\User;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Users', weight: 1)]
final class UserController extends Controller
{
    use CursorPaginates;

    public function index(ListUsersRequest $request): JsonResponse
    {
        $query = User::query()->orderBy('id');

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = UserResource::collection($page['data'])->resolve();

        return response()->json($page);
    }
}
