<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('hours', 8, 2);
            $table->text('description')->nullable();
            $table->dateTime('logged_at');
            $table->foreignId('client_invoice_item_id')
                ->nullable()
                ->constrained('client_invoice_items')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['task_id', 'user_id']);
            $table->index('logged_at');
            $table->index('client_invoice_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
