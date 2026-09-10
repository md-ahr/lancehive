<?php

use App\Features\Tenancy\Enums\FreelancerMembershipRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id')->constrained('freelancers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default(FreelancerMembershipRole::Member->value);
            $table->timestamps();

            $table->unique(['freelancer_id', 'user_id']);
            $table->index('user_id');
            $table->index(['freelancer_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_memberships');
    }
};
