<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plan_items', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->after('production_plan_machine_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_plan_items', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
            $table->dropColumn('machine_id');
        });
    }
};
