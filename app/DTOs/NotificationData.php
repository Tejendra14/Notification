<?php
namespace App\DTOs;

use App\Enums\NotificationType;

class NotificationData
{
    public function __construct(
        public readonly string $userId,
        public readonly NotificationType $type,
        public readonly string $channel,
        public readonly array $payload,
        public readonly ?string $tenantId = null,
        public readonly ?string $scheduledAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            type: NotificationType::from($data['type']),
            channel: $data['channel'],
            payload: $data['payload'],
            tenantId: $data['tenant_id'] ?? null,
            scheduledAt: $data['scheduled_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'type' => $this->type->value,
            'channel' => $this->channel,
            'payload' => $this->payload,
            'tenant_id' => $this->tenantId,
            'scheduled_at' => $this->scheduledAt,
        ];
    }
}