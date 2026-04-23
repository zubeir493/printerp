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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->date('hire_date');
            $table->string('status')->default('active'); // active, inactive, terminated
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            
            // Payroll related fields (Architecture for future use)
            $table->decimal('basic_salary', 15, 2)->default(0); // Monthly rate
            $table->decimal('hourly_overtime_rate', 15, 2)->default(0);
            $table->decimal('holiday_overtime_rate', 15, 2)->default(0);
            $table->string('payment_method')->nullable(); // Cash, Bank Transfer, etc.
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
