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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_type')->default('standard'); // standard, petty_cash, expense
            $table->foreignId('account_id')->nullable()->constrained('accounts'); // To override default asset account
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn(['payment_type', 'account_id']);
        });
    }
};
