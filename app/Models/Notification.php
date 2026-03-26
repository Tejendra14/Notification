<?php
namespace App\Models;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'channel',
        'payload',
        'status',
        'retry_count',
        'error_message',
        'processed_at',
        'scheduled_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'status' => NotificationStatus::class,
        'type' => NotificationType::class,
        'processed_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function markAsProcessed(): void
    {
        $this->update([
            'status' => NotificationStatus::SENT,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => NotificationStatus::FAILED,
            'error_message' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);
    }
}