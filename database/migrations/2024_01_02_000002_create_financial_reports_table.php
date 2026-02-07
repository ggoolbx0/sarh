<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_code')->unique();
            $table->enum('scope', ['employee', 'branch', 'department', 'company']);
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom']);
            $table->date('period_start');
            $table->date('period_end');

            // --- Scope References ---
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();

            // --- Attendance Summary ---
            $table->unsignedInteger('total_working_days')->default(0);
            $table->unsignedInteger('total_present_days')->default(0);
            $table->unsignedInteger('total_late_days')->default(0);
            $table->unsignedInteger('total_absent_days')->default(0);
            $table->unsignedInteger('total_leave_days')->default(0);

            // --- Time Analysis ---
            $table->unsignedInteger('total_delay_minutes')->default(0);
            $table->unsignedInteger('total_early_leave_minutes')->default(0);
            $table->unsignedInteger('total_overtime_minutes')->default(0);
            $table->unsignedInteger('total_worked_minutes')->default(0);

            // --- Financial Impact ---
            $table->decimal('total_salary_budget', 14, 2)->default(0);
            $table->decimal('total_delay_cost', 14, 2)->default(0);
            $table->decimal('total_early_leave_cost', 14, 2)->default(0);
            $table->decimal('total_overtime_cost', 14, 2)->default(0);
            $table->decimal('net_financial_impact', 14, 2)->default(0)->comment('losses - overtime credits');
            $table->decimal('loss_percentage', 6, 2)->default(0)->comment('(total_delay_cost / total_salary_budget) × 100');

            // --- Metadata ---
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->json('meta')->nullable()->comment('Extra calculations, charts data, etc.');

            $table->timestamps();

            $table->index(['scope', 'period_start', 'period_end']);
            $table->index(['branch_id', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
