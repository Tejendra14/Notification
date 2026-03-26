<?php
namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Events\NotificationFailed;
use App\Events\NotificationProcessed;
use App\Models\Notification;
use App\Repositories\NotificationRepository;
use App\Services\NotificationChannelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [30, 120, 300]; // Exponential backoff: 30s, 2min, 5min
    
    public function __construct(
        private string $notificationId
    ) {}
    
    public function handle(
        NotificationRepository $repository,
        NotificationChannelService $channelService
    ): void {
        $notification = $repository->find($this->notificationId);
        
        if (!$notification) {
            Log::warning('Notification not found', ['id' => $this->notificationId]);
            return;
        }
        
        // Mark as processing
        $repository->updateStatus($this->notificationId, NotificationStatus::PROCESSING);
        
        try {
            // Simulate sending notification
            $result = $channelService->send($notification);
            
            // Mark as sent
            $repository->updateStatus($this->notificationId, NotificationStatus::SENT);
            
            // Log success
            Log::info('Notification processed successfully', [
                'notification_id' => $this->notificationId,
                'user_id' => $notification->user_id,
                'type' => $notification->type->value,
            ]);
            
            // Fire event for webhooks
            event(new NotificationProcessed($notification, $result));
            
        } catch (\Exception $e) {
            // Mark as failed
            $repository->updateStatus(
                $this->notificationId,
                NotificationStatus::FAILED,
                $e->getMessage()
            );
            
            // Log error
            Log::error('Notification processing failed', [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            // Fire event for webhooks
            event(new NotificationFailed($notification, $e->getMessage()));
            
            // Re-throw to trigger retry
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::critical('Notification job failed after all retries', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
        
        // Mark as permanently failed if not already marked
        $repository = app(NotificationRepository::class);
        $notification = $repository->find($this->notificationId);
        
        if ($notification && $notification->status !== NotificationStatus::FAILED) {
            $repository->updateStatus(
                $this->notificationId,
                NotificationStatus::FAILED,
                'Max retries exceeded: ' . $exception->getMessage()
            );
        }
    }
}