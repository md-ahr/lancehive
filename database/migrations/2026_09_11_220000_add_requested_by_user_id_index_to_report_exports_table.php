<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            if (! $this->indexExists('report_exports', 'report_exports_requested_by_user_id_index')) {
                $table->index('requested_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            if ($this->indexExists('report_exports', 'report_exports_requested_by_user_id_index')) {
                $table->dropIndex(['requested_by_user_id']);
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return in_array($indexName, Schema::getIndexListing($table), true);
    }
};
