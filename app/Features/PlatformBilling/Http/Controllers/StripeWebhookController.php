<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Http\Controllers;

use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\PlatformBilling\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Webhooks', weight: 70)]
final class StripeWebhookController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptionService) {}

    public function handle(Request $request): MessageResource
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->subscriptionService->syncFromStripeWebhook($payload);

        return new MessageResource(['message' => 'Webhook handled.']);
    }
}
