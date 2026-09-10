<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Notifications;

use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RenewalReceiptNotification extends Notification implements ShouldQueue
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
            ->subject('Subscription renewal receipt')
            ->line('Thank you! Your '.$this->subscription->plan?->name.' subscription has been renewed.')
            ->action('View subscription', config('app.frontend_url').'/subscription');
    }
}
