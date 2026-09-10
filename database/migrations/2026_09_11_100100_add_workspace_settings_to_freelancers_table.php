<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('freelancers', function (Blueprint $table) {
            $table->char('default_currency', 3)->default('BDT')->after('owner_user_id');
            $table->string('invoice_number_prefix', 20)->default('INV')->after('default_currency');
            $table->decimal('default_tax_rate', 5, 2)->nullable()->after('invoice_number_prefix');
            $table->text('invoice_footer_notes')->nullable()->after('default_tax_rate');
            $table->string('business_name')->nullable()->after('invoice_footer_notes');
            $table->string('business_email')->nullable()->after('business_name');
            $table->text('business_address')->nullable()->after('business_email');
        });
    }

    public function down(): void
    {
        Schema::table('freelancers', function (Blueprint $table) {
            $table->dropColumn([
                'default_currency',
                'invoice_number_prefix',
                'default_tax_rate',
                'invoice_footer_notes',
                'business_name',
                'business_email',
                'business_address',
            ]);
        });
    }
};
