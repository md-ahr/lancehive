<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone', 64)->default('UTC')->after('role');
            $table->string('locale', 10)->default('en')->after('timezone');
            $table->jsonb('notification_preferences')->default('{"subscription_alerts":true,"workspace_invites":true,"invoice_activity":true}')->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'locale', 'notification_preferences']);
        });
    }
};
