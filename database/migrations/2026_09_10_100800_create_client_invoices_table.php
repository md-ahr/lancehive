<?php

use App\Features\ClientBilling\Enums\ClientInvoiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id')->constrained('freelancers')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('invoice_number');
            $table->string('status')->default(ClientInvoiceStatus::Draft->value);
            $table->char('currency', 3)->default('BDT');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('issued_at')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('bill_to_name');
            $table->string('bill_to_email')->nullable();
            $table->text('bill_to_address')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['freelancer_id', 'invoice_number']);
            $table->index('project_id');
            $table->index(['freelancer_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_invoices');
    }
};
