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
        Schema::table('job_orders', function (Blueprint $table) {
            $table->enum('production_mode', ['make_to_order', 'make_to_stock'])->default('make_to_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn('production_mode');
        });
    }
};
