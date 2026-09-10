<?php

declare(strict_types=1);

it('schedules mark overdue client invoices daily', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('MarkOverdueClientInvoices')
        ->assertSuccessful();
});

it('schedules trial ending notifications daily', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('subscriptions:notify-trial-ending')
        ->assertSuccessful();
});
