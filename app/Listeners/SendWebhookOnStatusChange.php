<?php
namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\NotificationFailed;
use App\Events\NotificationProcessed;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWebhookOnStatusChange implements ShouldQueue
{
    public function __construct(
        private WebhookService $webhookService
    ) {}
    
    public function handle($event): void
    {
        $eventType = match ($event::class) {
            NotificationCreated::class => 'created',
            NotificationProcessed::class => 'processed',
            NotificationFailed::class => 'failed',
            default => null,
        };
        
        if ($eventType && $event->notification) {
            $this->webhookService->sendWebhook(
                $event->notification,
                $eventType,
                $event->data ?? null
            );
        }
    }
}