<?php

declare(strict_types=1);

namespace App\Features\ClientBilling\Http\Controllers;

use App\Core\Pagination\Concerns\CursorPaginates;
use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\ClientBilling\Http\Requests\DestroyClientInvoiceRequest;
use App\Features\ClientBilling\Http\Requests\IndexProjectClientInvoiceRequest;
use App\Features\ClientBilling\Http\Requests\ShowClientInvoiceRequest;
use App\Features\ClientBilling\Http\Requests\StoreClientInvoiceItemRequest;
use App\Features\ClientBilling\Http\Requests\StoreClientInvoicePaymentRequest;
use App\Features\ClientBilling\Http\Requests\StoreClientInvoiceRequest;
use App\Features\ClientBilling\Http\Requests\UpdateClientInvoiceRequest;
use App\Features\ClientBilling\Http\Resources\ClientInvoiceItemResource;
use App\Features\ClientBilling\Http\Resources\ClientInvoicePaymentResource;
use App\Features\ClientBilling\Http\Resources\ClientInvoiceResource;
use App\Features\ClientBilling\Models\ClientInvoice;
use App\Features\ClientBilling\Services\ClientInvoiceService;
use App\Features\Delivery\Models\Project;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Client Invoices', weight: 50)]
final class ClientInvoiceController extends Controller
{
    use CursorPaginates;

    public function __construct(
        private readonly ClientInvoiceService $clientInvoiceService,
    ) {}

    #[Endpoint(title: 'List project client invoices', description: 'Cursor-paginated client invoices for a project.')]
    public function indexForProject(IndexProjectClientInvoiceRequest $request, Project $project): JsonResponse
    {
        $query = ClientInvoice::query()
            ->where('project_id', $project->id)
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = ClientInvoiceResource::collection($page['data'])->resolve();

        return response()->json($page);
    }

    public function store(StoreClientInvoiceRequest $request, Project $project): JsonResponse
    {
        $invoice = $this->clientInvoiceService->createDraft($project, $request->validated());

        if ($request->boolean('prefill_unbilled_time')) {
            $this->clientInvoiceService->prefillUnbilledTimeLogs($invoice);
            $invoice = $invoice->fresh();
        }

        return (new ClientInvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShowClientInvoiceRequest $request, ClientInvoice $clientInvoice): ClientInvoiceResource
    {
        $clientInvoice->load(['items', 'payments']);

        return new ClientInvoiceResource($clientInvoice);
    }

    public function update(UpdateClientInvoiceRequest $request, ClientInvoice $clientInvoice): ClientInvoiceResource
    {
        $invoice = $this->clientInvoiceService->updateInvoice($clientInvoice, $request->validated());

        return new ClientInvoiceResource($invoice);
    }

    public function destroy(DestroyClientInvoiceRequest $request, ClientInvoice $clientInvoice): MessageResource
    {
        $this->clientInvoiceService->assertDeletable($clientInvoice);
        $clientInvoice->delete();

        return new MessageResource([
            'message' => 'Client invoice deleted successfully.',
        ]);
    }

    public function storeItem(StoreClientInvoiceItemRequest $request, ClientInvoice $clientInvoice): JsonResponse
    {
        $item = $this->clientInvoiceService->addItem($clientInvoice, $request->validated());

        return (new ClientInvoiceItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function storePayment(StoreClientInvoicePaymentRequest $request, ClientInvoice $clientInvoice): JsonResponse
    {
        $payment = $this->clientInvoiceService->recordPayment($clientInvoice, $request->validated());

        return (new ClientInvoicePaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }
}
