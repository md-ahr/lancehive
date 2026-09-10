<?php

declare(strict_types=1);

namespace App\Features\Admin\Http\Controllers;

use App\Features\Admin\Http\Requests\IndexPlanRequest;
use App\Features\Admin\Http\Requests\StorePlanRequest;
use App\Features\Admin\Http\Requests\UpdatePlanRequest;
use App\Features\PlatformBilling\Cache\PlanCache;
use App\Features\PlatformBilling\Http\Resources\PlanResource;
use App\Features\PlatformBilling\Models\Plan;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Admin', weight: 10)]
final class PlanController extends Controller
{
    public function __construct(private readonly PlanCache $planCache) {}

    public function index(IndexPlanRequest $request): JsonResponse
    {
        $query = Plan::query()->orderBy('sort_order');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json([
            'data' => PlanResource::collection($query->get()),
        ]);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = Plan::query()->create([
            ...$request->validated(),
            'currency' => $request->validated('currency', 'BDT'),
            'is_custom' => $request->boolean('is_custom'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->planCache->forget();

        return (new PlanResource($plan))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): PlanResource
    {
        $plan->update($request->validated());
        $this->planCache->forget();

        return new PlanResource($plan->fresh());
    }
}
