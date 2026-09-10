<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Http\Controllers;

use App\Core\ClientPortal\ClientContext;
use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Features\ClientPortal\Http\Requests\ShowPortalClientRequest;
use App\Features\Delivery\Http\Resources\ClientResource;
use App\Features\Delivery\Models\Client;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;

#[HeaderParameter('X-Client-Id', description: 'Active client organization ID', required: true)]
#[Group('Client Portal', weight: 33)]
final class PortalClientController extends Controller
{
    public function __construct(private readonly ClientContext $clientContext) {}

    public function show(ShowPortalClientRequest $request): ClientResource
    {
        $clientId = $this->clientContext->clientId();

        if ($clientId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active client organization is required.');
        }

        $client = Client::query()->withoutGlobalScopes()->findOrFail($clientId);

        return new ClientResource($client);
    }
}
