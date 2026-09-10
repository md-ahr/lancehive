<?php

declare(strict_types=1);

use App\Features\Delivery\Http\Resources\TimeLogResource;
use App\Features\Delivery\Models\TimeLog;

it('serializes time log resource with expected keys and decimal hours', function () {
    $timeLog = TimeLog::factory()->create([
        'hours' => '2.50',
        'description' => 'Initial wireframes',
        'logged_at' => '2026-03-08 09:00:00',
    ]);

    $payload = (new TimeLogResource($timeLog))->resolve();

    expect($payload)->toMatchArray([
        'id' => $timeLog->id,
        'task_id' => $timeLog->task_id,
        'user_id' => $timeLog->user_id,
        'hours' => '2.50',
        'description' => 'Initial wireframes',
        'client_invoice_item_id' => null,
    ])
        ->and($payload)->toHaveKeys(['logged_at', 'created_at', 'updated_at']);
});

it('formats hours as a two-decimal string', function () {
    $timeLog = TimeLog::factory()->create(['hours' => 3]);

    $payload = (new TimeLogResource($timeLog))->resolve();

    expect($payload['hours'])->toBe('3.00');
});
