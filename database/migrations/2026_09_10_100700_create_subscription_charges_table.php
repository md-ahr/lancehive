<?php

use App\Features\PlatformBilling\Enums\SubscriptionChargeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('BDT');
            $table->string('status')->default(SubscriptionChargeStatus::Pending->value);
            $table->dateTime('paid_at')->nullable();
            $table->string('provider_charge_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_charges');
    }
};
