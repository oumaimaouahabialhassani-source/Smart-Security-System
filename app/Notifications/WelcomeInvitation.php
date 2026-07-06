<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeInvitation extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
            'welcome' => 1,
        ]);

        return (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting('Welcome, '.$notifiable->first_name.'!')
            ->line('An account has been created for you on '.config('app.name').' with the role of '.$notifiable->role->label().'.')
            ->line('Click the button below to choose your password and activate your account.')
            ->action('Set Your Password', $url)
            ->line('This link expires in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes. If it expires, use "Forgot Password?" on the login page to request a new one.')
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
