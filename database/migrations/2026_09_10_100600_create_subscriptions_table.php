<?php

use App\Features\PlatformBilling\Enums\SubscriptionProvider;
use App\Features\PlatformBilling\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id')->unique()->constrained('freelancers')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('status')->default(SubscriptionStatus::Trialing->value);
            $table->string('billing_interval')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('current_period_start')->nullable();
            $table->dateTime('current_period_end')->nullable();
            $table->dateTime('read_only_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->string('provider')->default(SubscriptionProvider::Manual->value);
            $table->string('provider_subscription_id')->nullable();
            $table->timestamps();

            $table->index('provider_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
