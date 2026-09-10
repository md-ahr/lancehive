<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Http\Controllers;

use App\Features\Tenancy\Http\Resources\MeResource;
use App\Features\Tenancy\Services\MeService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\Request;

#[Group('Authentication', weight: 0)]
#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID (optional — selects active_freelancer and subscription in the response)', required: false)]
#[HeaderParameter('X-Client-Id', description: 'Active client organization ID (optional — selects active_client in the response)', required: false)]
final class MeController extends Controller
{
    public function show(Request $request, MeService $meService): MeResource
    {
        $freelancerHeader = $request->header('X-Freelancer-Id');
        $freelancerId = ($freelancerHeader !== null && $freelancerHeader !== '') ? (int) $freelancerHeader : null;

        $clientHeader = $request->header('X-Client-Id');
        $clientId = ($clientHeader !== null && $clientHeader !== '') ? (int) $clientHeader : null;

        return new MeResource($meService->forUser($request->user(), $freelancerId, $clientId));
    }
}
