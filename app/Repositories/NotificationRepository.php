<?php
namespace App\Repositories;

use App\DTOs\NotificationData;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class NotificationRepository
{
    private const CACHE_TTL = 3600; // 1 hour

    public function create(NotificationData $data): Notification
    {
        return Notification::create($data->toArray());
    }

    public function find(string $id): ?Notification
    {
        return Cache::remember(
            "notification:{$id}",
            self::CACHE_TTL,
            fn() => Notification::find($id)
        );
    }

    public function updateStatus(string $id, NotificationStatus $status, ?string $error = null): bool
    {
        $notification = $this->find($id);
        
        if (!$notification) {
            return false;
        }

        $updated = $notification->update([
            'status' => $status,
            'error_message' => $error,
            'processed_at' => $status === NotificationStatus::SENT ? now() : $notification->processed_at,
        ]);

        if ($updated) {
            Cache::forget("notification:{$id}");
        }

        return $updated;
    }

    public function getRecent(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Notification::query()->latest();

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->paginate($perPage);
    }

    public function getSummary(?string $tenantId = null): array
    {
        $cacheKey = $tenantId 
            ? "notification_summary:{$tenantId}" 
            : "notification_summary:global";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            $query = Notification::query();
            
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            return [
                'total' => $query->count(),
                'sent' => (clone $query)->where('status', NotificationStatus::SENT)->count(),
                'failed' => (clone $query)->where('status', NotificationStatus::FAILED)->count(),
                'pending' => (clone $query)->where('status', NotificationStatus::PENDING)->count(),
                'processing' => (clone $query)->where('status', NotificationStatus::PROCESSING)->count(),
            ];
        });
    }
}