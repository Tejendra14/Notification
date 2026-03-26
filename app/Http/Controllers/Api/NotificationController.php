<?php
namespace App\Http\Controllers\Api;

use App\DTOs\NotificationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Services\NotificationService;
use App\Services\RateLimitService;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private RateLimitService $rateLimitService
    ) {}
    
    public function store(NotificationRequest $request): JsonResponse
    {
        // Remove this line: dd($request->all());
        $data = NotificationData::fromArray($request->validated());
        
        $result = $this->notificationService->publishNotification($data);
        
        return response()->json($result, 202);
    }
    
    public function rateLimitStatus(string $userId): JsonResponse
    {
        return response()->json([
            'user_id' => $userId,
            'max_per_hour' => $this->rateLimitService->getMaxNotifications(),
            'remaining' => $this->rateLimitService->getRemainingCount($userId),
            'resets_in' => $this->rateLimitService->getTimeWindow(),
        ]);
    }
    
    public function retry(string $id): JsonResponse
    {
        $this->notificationService->retryFailedNotification($id);
        
        return response()->json([
            'message' => 'Notification queued for retry',
            'notification_id' => $id,
        ]);
    }
}