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
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->unique(['inventory_item_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropUnique(['inventory_item_id', 'warehouse_id']);
        });
    }
};
