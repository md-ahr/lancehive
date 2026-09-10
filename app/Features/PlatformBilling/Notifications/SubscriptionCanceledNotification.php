<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Notifications;

use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubscriptionCanceledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Subscription $subscription) {}

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
            ->subject('Your LanceHive subscription has been canceled')
            ->line('Your subscription to '.$this->subscription->plan?->name.' has been canceled.')
            ->line('You can continue using your workspace until the end of the current billing period.')
            ->action('Manage subscription', config('app.frontend_url').'/subscription');
    }
}
