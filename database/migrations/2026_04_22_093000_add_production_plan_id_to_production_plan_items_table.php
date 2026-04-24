<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plan_items', function (Blueprint $table) {
            $table->foreignId('production_plan_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_plan_items', function (Blueprint $table) {
            $table->dropForeign(['production_plan_id']);
            $table->dropColumn('production_plan_id');
        });
    }
};
