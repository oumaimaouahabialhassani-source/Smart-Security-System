<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * In-app notification fanned out to the security staff whenever an
 * alert is raised (Alert::raise). Stored on the database channel so
 * each recipient carries their own read/unread state.
 */
class SecurityAlert extends Notification
{
    public function __construct(public Alert $alert)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'title' => $this->alert->type,
            'detail' => Str::limit($this->alert->description, 90),
            'severity' => $this->alert->severity->value,
            'severity_label' => $this->alert->severity->label(),
            'badge' => $this->alert->severity->badge(),
        ];
    }
}
