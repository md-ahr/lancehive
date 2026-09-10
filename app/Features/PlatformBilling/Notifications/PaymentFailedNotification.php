<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Notifications;

use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Subscription $subscription) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User
            && $notifiable->prefersNotification(
                'subscription_alerts',
                $this->subscription->freelancer,
            )) {
            return ['mail'];
        }

        return [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment failed for '.$this->subscription->plan?->name.' subscription')
            ->line('We could not process your latest subscription payment.')
            ->line('Please update your billing details to avoid losing write access to your workspace.')
            ->action('Manage subscription', config('app.frontend_url').'/subscription');
    }
}
