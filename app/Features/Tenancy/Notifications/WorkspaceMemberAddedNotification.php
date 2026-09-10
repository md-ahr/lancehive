<?php

declare(strict_types=1);

namespace App\Features\Tenancy\Notifications;

use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WorkspaceMemberAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Freelancer $freelancer) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been added to '.$this->freelancer->name)
            ->line('You now have access to the '.$this->freelancer->name.' workspace on LanceHive.')
            ->line('Sign in with your existing account to get started.');
    }
}
