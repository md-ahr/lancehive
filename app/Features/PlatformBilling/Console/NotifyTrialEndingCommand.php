<?php

declare(strict_types=1);

namespace App\Features\PlatformBilling\Console;

use App\Features\Auth\Models\User;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use App\Features\PlatformBilling\Models\Subscription;
use App\Features\PlatformBilling\Notifications\TrialEndingSoonNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

final class NotifyTrialEndingCommand extends Command
{
    protected $signature = 'subscriptions:notify-trial-ending';

    protected $description = 'Notify workspace owners whose trial ends in 3 days';

    public function handle(): int
    {
        $targetDate = now()->addDays(3)->toDateString();

        $subscriptions = Subscription::query()
            ->with(['freelancer.owner', 'plan'])
            ->where('status', SubscriptionStatus::Trialing)
            ->whereDate('trial_ends_at', $targetDate)
            ->whereNull('trial_ending_notified_at')
            ->get();

        foreach ($subscriptions as $subscription) {
            $owner = $subscription->freelancer?->owner;

            if ($owner instanceof User) {
                Notification::send($owner, new TrialEndingSoonNotification($subscription));
                $subscription->update(['trial_ending_notified_at' => now()]);
            }
        }

        $this->info('Sent '.$subscriptions->count().' trial ending notifications.');

        return self::SUCCESS;
    }
}
