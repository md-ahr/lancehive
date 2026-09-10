<?php

declare(strict_types=1);

use App\Features\Delivery\Models\TimeLog;

it('belongs to task user and optional invoice item', function () {
    $timeLog = TimeLog::factory()->create();

    expect($timeLog->task)->not->toBeNull()
        ->and($timeLog->user)->not->toBeNull()
        ->and($timeLog->client_invoice_item_id)->toBeNull();
});
