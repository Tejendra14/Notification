<?php
namespace App\Services;

use App\DTOs\NotificationData;
use App\Enums\NotificationStatus;
use App\Events\NotificationCreated;
use App\Jobs\ProcessNotificationJob;
use App\Repositories\NotificationRepository;
use App\Repositories\NotificationTemplateRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private RateLimitService $rateLimitService,
        private NotificationTemplateRepository $templateRepository,
    ) {}

    public function publishNotification(NotificationData $data): array
    {
        // Check rate limit
        $this->rateLimitService->checkLimit($data->userId);
        
        // Apply template if provided
        if (isset($data->payload['template_name'])) {
            $data = $this->applyTemplate($data);
        }
        
        // Create notification record
        $notification = DB::transaction(function () use ($data) {
            $notification = $this->notificationRepository->create($data);
            
            // Dispatch to queue
            ProcessNotificationJob::dispatch($notification->id)
                ->onQueue('notifications');
            
            return $notification;
        });
        
        // Increment rate limit counter
        $this->rateLimitService->incrementCount($data->userId);
        
        // Fire event for webhooks
        event(new NotificationCreated($notification));
        
        return [
            'id' => $notification->id,
            'status' => $notification->status,
            'message' => 'Notification queued successfully',
            'remaining' => $this->rateLimitService->getRemainingCount($data->userId),
        ];
    }
    
    private function applyTemplate(NotificationData $data): NotificationData
    {
        $template = $this->templateRepository->findByName(
            $data->payload['template_name'],
            $data->tenantId
        );
        
        if ($template) {
            $data->payload['subject'] = $this->replaceVariables(
                $template->subject ?? '',
                $data->payload['variables'] ?? []
            );
            $data->payload['content'] = $this->replaceVariables(
                $template->content,
                $data->payload['variables'] ?? []
            );
        }
        
        return $data;
    }
    
    private function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace("{{{$key}}}", $value, $text);
        }
        
        return $text;
    }
    
    public function retryFailedNotification(string $notificationId): void
    {
        $notification = $this->notificationRepository->find($notificationId);
        
        if (!$notification || $notification->status !== NotificationStatus::FAILED) {
            throw new \InvalidArgumentException('Cannot retry non-failed notification');
        }
        
        $this->notificationRepository->updateStatus(
            $notificationId,
            NotificationStatus::PENDING,
            null
        );
        
        ProcessNotificationJob::dispatch($notificationId)
            ->onQueue('notifications');
        
        Log::info('Retrying failed notification', ['notification_id' => $notificationId]);
    }
}