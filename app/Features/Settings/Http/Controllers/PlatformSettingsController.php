<?php

declare(strict_types=1);

namespace App\Features\Settings\Http\Controllers;

use App\Features\Settings\Http\Requests\ShowPlatformSettingsRequest;
use App\Features\Settings\Http\Requests\UpdatePlatformSettingsRequest;
use App\Features\Settings\Http\Resources\PlatformSettingsResource;
use App\Features\Settings\Services\PlatformSettingsService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;

#[Group('Settings', weight: 15)]
final class PlatformSettingsController extends Controller
{
    public function __construct(private readonly PlatformSettingsService $platformSettingsService) {}

    public function show(ShowPlatformSettingsRequest $request): PlatformSettingsResource
    {
        return new PlatformSettingsResource($this->platformSettingsService->get());
    }

    public function update(UpdatePlatformSettingsRequest $request): PlatformSettingsResource
    {
        $settings = $this->platformSettingsService->update($request->validated());

        return new PlatformSettingsResource($settings);
    }
}
