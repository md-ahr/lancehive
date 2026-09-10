<?php

declare(strict_types=1);

namespace App\Features\Auth\Http\Controllers;

use App\Features\Auth\Http\Resources\UserCollectionResource;
use App\Features\Auth\Models\User;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;

#[Group('Users', weight: 1)]
final class UserController extends Controller
{
    public function index(): UserCollectionResource
    {
        $users = User::query()
            ->orderBy('name')
            ->get();

        return new UserCollectionResource($users);
    }
}
