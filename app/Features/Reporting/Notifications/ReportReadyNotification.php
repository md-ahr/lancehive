<?php

declare(strict_types=1);

namespace App\Features\Reporting\Notifications;

use App\Features\Reporting\Models\ReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ReportExport $export) {}

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
            ->subject('Your report export is ready')
            ->line('Your '.$this->export->report_type->value.' CSV export has completed.')
            ->line('Export ID: '.$this->export->id)
            ->line('Row count: '.($this->export->row_count ?? 0))
            ->action('View exports', config('app.frontend_url').'/reports/exports/'.$this->export->id);
    }
}
