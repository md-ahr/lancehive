<?php

declare(strict_types=1);

namespace App\Features\ClientPortal\Notifications;

use App\Features\Delivery\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ClientPortalMemberAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Client $client) {}

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
            ->subject('You have been added to '.$this->client->name)
            ->line('You now have access to the '.$this->client->name.' client portal on LanceHive.')
            ->line('Sign in with your existing account to view projects and invoices.');
    }
}
