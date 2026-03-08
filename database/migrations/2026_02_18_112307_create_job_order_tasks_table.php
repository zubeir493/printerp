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

        Schema::create('job_order_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained();
            $table->string('name');
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2);
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
        Schema::dropIfExists('job_order_tasks');
        Schema::enableForeignKeyConstraints();
    }
};
