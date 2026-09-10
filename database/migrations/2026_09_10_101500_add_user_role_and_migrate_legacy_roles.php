<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', [UserRole::Freelancer->value, UserRole::Client->value])
            ->update(['role' => UserRole::User->value]);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default(UserRole::User->value)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default(UserRole::Client->value)->change();
        });
    }
};
