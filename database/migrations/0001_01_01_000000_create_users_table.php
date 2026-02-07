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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // --- Identity ---
            $table->string('employee_id')->unique()->comment('Auto-generated badge number');
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('national_id')->unique()->nullable();
            $table->string('avatar')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->date('date_of_birth')->nullable();

            // --- Organizational ---
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('direct_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('job_title_ar')->nullable();
            $table->string('job_title_en')->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->enum('status', ['active', 'suspended', 'terminated', 'on_leave'])->default('active');

            // --- Financial (Salary-to-Minute Engine) ---
            $table->decimal('basic_salary', 12, 2)->default(0)->comment('Monthly basic salary');
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);
            $table->unsignedSmallInteger('working_days_per_month')->default(22);
            $table->unsignedSmallInteger('working_hours_per_day')->default(8);
            // cost_per_minute = (basic_salary / working_days_per_month / working_hours_per_day / 60)

            // --- Security & RBAC ---
            $table->unsignedTinyInteger('security_level')->default(1)->comment('1-10 RBAC level');
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_trap_target')->default(false)->comment('Psychological trap system flag');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();

            // --- Gamification ---
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedSmallInteger('current_streak')->default(0)->comment('Consecutive on-time days');
            $table->unsignedSmallInteger('longest_streak')->default(0);

            // --- Preferences ---
            $table->string('locale', 5)->default('ar');
            $table->string('timezone', 50)->default('Asia/Riyadh');

            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            // --- Indexes ---
            $table->index(['branch_id', 'status']);
            $table->index(['department_id', 'status']);
            $table->index('security_level');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
