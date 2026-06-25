<?php

namespace App\DataTransferObjects;

use DateTimeInterface;

final class ActivityLogEntry
{
    public function __construct(
        public readonly string $channel,
        public readonly ?int $actorUserId,
        public readonly string $actorType,
        public readonly string $action,
        public readonly ?string $subjectType = null,
        public readonly ?string $subjectId = null,
        public readonly ?string $subjectLabel = null,
        public readonly ?string $description = null,
        public readonly array $properties = [],
        public readonly ?array $oldValues = null,
        public readonly ?array $newValues = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $requestId = null,
        public readonly ?string $eventUuid = null,
        public readonly ?string $deviceId = null,
        public readonly ?string $appVersion = null,
        public readonly ?string $platform = null,
        public readonly string $status = 'success',
        public readonly ?DateTimeInterface $occurredAt = null,
    ) {}

    public function toArray(): array
    {
        $now = now();

        return [
            'channel' => $this->channel,
            'actor_user_id' => $this->actorUserId,
            'actor_type' => $this->actorType,
            'action' => $this->action,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'subject_label' => $this->subjectLabel,
            'description' => $this->description,
            'properties' => $this->properties ?: null,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'request_id' => $this->requestId,
            'event_uuid' => $this->eventUuid,
            'device_id' => $this->deviceId,
            'app_version' => $this->appVersion,
            'platform' => $this->platform,
            'status' => $this->status,
            'occurred_at' => $this->occurredAt ?? $now,
            'created_at' => $now,
        ];
    }
}
