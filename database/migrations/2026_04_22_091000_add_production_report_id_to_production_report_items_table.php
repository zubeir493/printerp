<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_report_items', function (Blueprint $table) {
            $table->foreignId('production_report_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_report_items', function (Blueprint $table) {
            $table->dropForeign(['production_report_id']);
            $table->dropColumn('production_report_id');
        });
    }
};
