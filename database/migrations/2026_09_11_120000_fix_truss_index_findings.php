<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS clients_freelancer_id_index');
            DB::statement('DROP INDEX IF EXISTS subscription_charges_subscription_id_index');
            DB::statement('DROP INDEX IF EXISTS time_logs_unbilled_task_id_index');
        }

        Schema::table('client_memberships', function (Blueprint $table): void {
            if (! $this->indexExists('client_memberships', 'client_memberships_user_id_index')) {
                $table->index('user_id');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            if (! $this->indexExists('subscriptions', 'subscriptions_plan_id_index')) {
                $table->index('plan_id');
            }
        });

        Schema::table('time_logs', function (Blueprint $table): void {
            if (! $this->indexExists('time_logs', 'time_logs_user_id_index')) {
                $table->index('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table): void {
            if ($this->indexExists('time_logs', 'time_logs_user_id_index')) {
                $table->dropIndex(['user_id']);
            }
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            if ($this->indexExists('subscriptions', 'subscriptions_plan_id_index')) {
                $table->dropIndex(['plan_id']);
            }
        });

        Schema::table('client_memberships', function (Blueprint $table): void {
            if ($this->indexExists('client_memberships', 'client_memberships_user_id_index')) {
                $table->dropIndex(['user_id']);
            }
        });

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'CREATE INDEX IF NOT EXISTS clients_freelancer_id_index ON clients (freelancer_id)'
        );
        DB::statement(
            'CREATE INDEX IF NOT EXISTS subscription_charges_subscription_id_index ON subscription_charges (subscription_id)'
        );
        DB::statement(
            'CREATE INDEX time_logs_unbilled_task_id_index ON time_logs (task_id) WHERE client_invoice_item_id IS NULL'
        );
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return in_array($indexName, Schema::getIndexListing($table), true);
    }
};
