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
        Schema::disableForeignKeyConstraints();

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->decimal('quantity_on_hand', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('inventory_balances');
        Schema::enableForeignKeyConstraints();
    }
};
