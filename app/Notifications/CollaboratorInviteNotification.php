<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CollaboratorInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $inviteeEmail,
        public string $inviteUrl,
        public string $role = 'admin',
    ) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You’ve been invited to collaborate on PowerPod')
            ->greeting('Hi '.$this->inviteeEmail.'!')
            ->line('You’ve been invited as a '.strtoupper($this->role).' to manage the podcast (episodes and settings).')
            ->action('Accept Invitation', $this->inviteUrl)
            ->line('If you did not expect this invite, you can safely ignore this email.');
    }
}
