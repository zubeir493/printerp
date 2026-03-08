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
            $table->foreignId('warehouse_id')->nullable()->constrained();
            $table->timestamp('consumed_at')->nullable();
        });

        Schema::table('job_order_tasks', function (Blueprint $table) {
            $table->json('paper')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_order_tasks', function (Blueprint $table) {
            $table->dropColumn('paper');
        });

        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['warehouse_id', 'consumed_at']);
        });
    }
};
