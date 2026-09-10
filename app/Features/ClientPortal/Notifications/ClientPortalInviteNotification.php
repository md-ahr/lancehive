<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Notifications;

use App\Features\Delivery\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ClientPortalInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Client $client,
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
            ->subject('You have been invited to '.$this->client->name.' on LanceHive')
            ->line('You have been invited to access the '.$this->client->name.' client portal.')
            ->action('Set your password', $this->resetUrl)
            ->line('This link will expire after a limited time.');
    }
}
