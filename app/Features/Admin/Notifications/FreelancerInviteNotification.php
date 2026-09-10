<?php

declare(strict_types=1);

namespace App\Features\Admin\Notifications;

use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class FreelancerInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Freelancer $freelancer,
        private readonly string $resetUrl,
    ) {}

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
            ->subject('You have been invited to '.$this->freelancer->name)
            ->line('You have been invited to manage the '.$this->freelancer->name.' workspace on LanceHive.')
            ->action('Set your password', $this->resetUrl)
            ->line('If you did not expect this invitation, you can ignore this email.');
    }
}
