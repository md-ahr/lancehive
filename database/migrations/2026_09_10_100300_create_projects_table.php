<?php

use App\Features\Delivery\Enums\ProjectStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('freelancers')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('hourly_rate', 12, 2);
            $table->char('currency', 3)->default('BDT');
            $table->string('status')->default(ProjectStatus::Active->value);
            $table->date('deadline')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['freelancer_id', 'client_id']);
            $table->index(['freelancer_id', 'status']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
