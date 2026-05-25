<?php

namespace App\Listeners;

use App\Services\SubscriptionService;
use Laravel\Cashier\Events\WebhookHandled;

class SyncSubscriptionPlanCredits
{
    public function __construct(private SubscriptionService $subscriptionService) {}

    public function handle(WebhookHandled $event): void
    {
        $this->subscriptionService->handleWebhook($event);
    }
}
