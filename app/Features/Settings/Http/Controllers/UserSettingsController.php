<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Controllers;

use App\Features\Settings\Http\Requests\ShowUserSettingsRequest;
use App\Features\Settings\Http\Requests\UpdateUserSettingsRequest;
use App\Features\Settings\Http\Resources\UserSettingsResource;
use App\Features\Settings\Services\UserSettingsService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;

#[Group('Settings', weight: 15)]
final class UserSettingsController extends Controller
{
    public function __construct(private readonly UserSettingsService $userSettingsService) {}

    public function show(ShowUserSettingsRequest $request): UserSettingsResource
    {
        $user = $request->user();
        assert($user !== null);

        return new UserSettingsResource($this->userSettingsService->get($user));
    }

    public function update(UpdateUserSettingsRequest $request): UserSettingsResource
    {
        $user = $request->user();
        assert($user !== null);

        $updated = $this->userSettingsService->update($user, $request->validated());

        return new UserSettingsResource($this->userSettingsService->get($updated));
    }
}
