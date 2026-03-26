<?php
namespace App\Services;

use App\Exceptions\RateLimitExceededException;
use Illuminate\Support\Facades\Cache;

class RateLimitService
{
    private const MAX_NOTIFICATIONS = 10;
    private const TIME_WINDOW = 3600; // 1 hour in seconds

    public function checkLimit(string $userId): void
    {
        $key = "rate_limit:user:{$userId}";
        $currentCount = Cache::get($key, 0);

        if ($currentCount >= self::MAX_NOTIFICATIONS) {
            throw new RateLimitExceededException(
                "Rate limit exceeded. Maximum {$this->getMaxNotifications()} notifications per hour."
            );
        }
    }

    public function incrementCount(string $userId): void
    {
        $key = "rate_limit:user:{$userId}";
        
        Cache::increment($key);
        
        // Set expiry if this is the first notification
        if (Cache::get($key) === 1) {
            Cache::expire($key, self::TIME_WINDOW);
        }
    }

    public function getRemainingCount(string $userId): int
    {
        $key = "rate_limit:user:{$userId}";
        $currentCount = Cache::get($key, 0);
        
        return max(0, self::MAX_NOTIFICATIONS - $currentCount);
    }

    public function getMaxNotifications(): int
    {
        return self::MAX_NOTIFICATIONS;
    }

    public function getTimeWindow(): int
    {
        return self::TIME_WINDOW;
    }
}