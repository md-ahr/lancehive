<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Unbilled time-log lookups use the left prefix of time_logs_task_id_user_id_index.
    }

    public function down(): void
    {
        //
    }
};
