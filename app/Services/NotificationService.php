<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function notify(int $userId, int $type, ?int $actorId, ?string $notifiableType, ?int $notifiableId, array $data = []): void
    {
        if ($userId === $actorId) {
            return;
        }

        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'actor_id' => $actorId,
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiableId,
            'data' => $data,
            'is_read' => false,
        ]);
    }
}
