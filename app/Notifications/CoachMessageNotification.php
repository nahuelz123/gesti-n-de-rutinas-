<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class CoachMessageNotification extends Notification
{
    public function __construct(
        public User $coach,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'coach_id' => $this->coach->id,
            'coach_name' => $this->coach->name,
            'message' => $this->message,
        ];
    }
}
