<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Notifications;

use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TrialEndingSoonNotification extends Notification implements ShouldQueue
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
        $days = $this->subscription->daysRemaining() ?? 3;

        return (new MailMessage)
            ->subject('Your LanceHive trial ends in '.$days.' days')
            ->line('Your trial for '.$this->subscription->plan?->name.' ends soon.')
            ->line('Subscribe now to keep full access to your workspace.')
            ->action('Choose a plan', config('app.frontend_url').'/subscription');
    }
}
