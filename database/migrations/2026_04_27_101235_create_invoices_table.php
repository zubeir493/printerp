<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->enum('invoice_type', ['sales', 'purchase', 'service', 'receipt']);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->enum('order_type', ['sales_order', 'purchase_order', 'job_order', 'payment']);
            $table->foreignId('partner_id')->constrained();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('balance_due', 12, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->string('filename');
            $table->string('file_path');
            $table->timestamp('emailed_at')->nullable();
            $table->string('email_recipient')->nullable();
            $table->json('tax_calculations')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();

            $table->index(['invoice_type', 'status']);
            $table->index(['partner_id', 'invoice_date']);
            $table->index(['order_type', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
