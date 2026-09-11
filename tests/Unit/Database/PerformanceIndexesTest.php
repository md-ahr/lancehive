<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('uses expected indexes for tenant and billing query paths', function () {
    if (Schema::getConnection()->getDriverName() !== 'pgsql') {
        expect(true)->toBeTrue();

        return;
    }

    $plans = [
        'clients tenant list' => "EXPLAIN SELECT * FROM clients WHERE freelancer_id = 1 AND status = 'active'",
        'memberships for me' => 'EXPLAIN SELECT * FROM freelancer_memberships WHERE user_id = 1',
        'overdue invoices' => "EXPLAIN SELECT * FROM client_invoices WHERE status = 'sent' AND due_date < CURRENT_DATE",
        'subscription webhook lookup' => 'EXPLAIN SELECT * FROM subscriptions WHERE provider_subscription_id = \'sub_test\'',
        'unbilled time logs' => 'EXPLAIN SELECT * FROM time_logs WHERE task_id = 1 AND client_invoice_item_id IS NULL',
    ];

    foreach ($plans as $label => $sql) {
        $rows = DB::select($sql);
        $plan = collect($rows)->pluck('QUERY PLAN')->implode(' ');

        expect($plan)->toContain('Index')
            ->and($plan)->not->toContain('Seq Scan on clients')
            ->and($plan)->not->toContain('Seq Scan on freelancer_memberships')
            ->and($plan)->not->toContain('Seq Scan on client_invoices')
            ->and($plan)->not->toContain('Seq Scan on subscriptions');
    }
});

it('indexes foreign keys without redundant left-prefix duplicates', function () {
    if (Schema::getConnection()->getDriverName() !== 'pgsql') {
        expect(true)->toBeTrue();

        return;
    }

    $requiredIndexes = [
        'client_memberships_user_id_index',
        'subscriptions_plan_id_index',
        'time_logs_user_id_index',
        'report_exports_requested_by_user_id_index',
    ];

    foreach ($requiredIndexes as $indexName) {
        $index = DB::selectOne(
            'SELECT indexname FROM pg_indexes WHERE indexname = ?',
            [$indexName]
        );

        expect($index)->not->toBeNull("Expected index {$indexName} to exist.");
    }

    $redundantIndexes = [
        'clients_freelancer_id_index',
        'subscription_charges_subscription_id_index',
        'time_logs_unbilled_task_id_index',
    ];

    foreach ($redundantIndexes as $indexName) {
        $index = DB::selectOne(
            'SELECT indexname FROM pg_indexes WHERE indexname = ?',
            [$indexName]
        );

        expect($index)->toBeNull("Expected redundant index {$indexName} to be absent.");
    }
});
