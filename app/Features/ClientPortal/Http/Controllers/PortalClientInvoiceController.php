<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Http\Controllers;

use App\Core\ClientPortal\ClientContext;
use App\Core\Http\Enums\ApiErrorCode;
use App\Core\Http\Exceptions\ApiException;
use App\Core\Pagination\Concerns\CursorPaginates;
use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use App\Features\ClientBilling\Http\Resources\ClientInvoiceResource;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientPortal\Http\Requests\IndexPortalClientInvoiceRequest;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;

#[HeaderParameter('X-Client-Id', description: 'Active client organization ID', required: true)]
#[Group('Client Portal', weight: 33)]
final class PortalClientInvoiceController extends Controller
{
    use CursorPaginates;

    public function __construct(private readonly ClientContext $clientContext) {}

    public function index(IndexPortalClientInvoiceRequest $request): JsonResponse
    {
        $clientId = $this->clientContext->clientId();

        if ($clientId === null) {
            throw new ApiException(ApiErrorCode::Forbidden, 'Active client organization is required.');
        }

        $query = ClientInvoice::query()
            ->withoutGlobalScopes()
            ->whereHas('project', fn ($builder) => $builder
                ->withoutGlobalScopes()
                ->where('client_id', $clientId))
            ->whereNotIn('status', [
                ClientInvoiceStatus::Draft->value,
                ClientInvoiceStatus::Void->value,
            ])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = ClientInvoiceResource::collection($page['data'])->resolve();

        return response()->json($page);
    }
}
