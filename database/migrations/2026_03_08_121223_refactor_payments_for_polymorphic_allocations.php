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
        // Refactor payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['job_order_id']);
            $table->dropColumn('job_order_id');
        });

        // Refactor payment_allocations table
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropForeign(['sales_invoice_id']);
            $table->dropForeign(['purchase_invoice_id']);
            $table->dropColumn(['sales_invoice_id', 'purchase_invoice_id']);
            
            $table->morphs('allocatable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropMorphs('allocatable');
            $table->foreignId('sales_invoice_id')->nullable()->constrained();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('job_order_id')->nullable()->constrained('job_orders')->onDelete('set null');
        });
    }
};
