<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('transaction_type')->default('customer_receipt')->after('payment_number');
            $table->foreignId('expense_account_id')->nullable()->after('account_id')->constrained('accounts');
            $table->foreignId('petty_cash_account_id')->nullable()->after('expense_account_id')->constrained('accounts');
        });

        DB::statement("
            UPDATE payments
            SET transaction_type = CASE
                WHEN payment_type = 'expense' THEN 'direct_expense'
                WHEN payment_type = 'petty_cash' AND direction = 'inbound' THEN 'petty_cash_funding'
                WHEN payment_type = 'petty_cash' AND direction = 'outbound' THEN 'petty_cash_expense'
                WHEN direction = 'outbound' THEN 'supplier_payment'
                ELSE 'customer_receipt'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['expense_account_id']);
            $table->dropForeign(['petty_cash_account_id']);
            $table->dropColumn(['transaction_type', 'expense_account_id', 'petty_cash_account_id']);
        });
    }
};
