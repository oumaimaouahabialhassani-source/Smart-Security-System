<?php

namespace App\Notifications;

use App\Models\AiAlert;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Fanned out to the security staff whenever the AI Security Bot
 * raises a high or critical alert. Always stored on the database
 * channel (dashboard bell); also mailed when the recipient opted
 * into email notifications.
 */
class AiBotAlert extends Notification
{
    public function __construct(public AiAlert $alert)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notification_preferences['email'] ?? false) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'ai_alert_id' => $this->alert->id,
            'title' => 'AI Bot: '.$this->alert->event_type,
            'detail' => Str::limit($this->alert->description, 90),
            'severity' => $this->alert->risk_level->value,
            'severity_label' => $this->alert->risk_level->label(),
            'badge' => $this->alert->risk_level->badge(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->alert->risk_level->label()}] AI Security Bot — {$this->alert->event_type}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The AI Security Bot detected a {$this->alert->risk_level->label()} risk event ({$this->alert->ai_code}).")
            ->line($this->alert->description)
            ->line('AI analysis: '.$this->alert->analysis)
            ->line('Recommended action: '.$this->alert->recommendation->label())
            ->action('Open AI Alerts', route('ai.alerts'))
            ->line('This message was generated automatically by the AI Security Bot.');
    }
}
