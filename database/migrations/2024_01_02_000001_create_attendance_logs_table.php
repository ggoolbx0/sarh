<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('attendance_date');

            // --- Check-in ---
            $table->timestamp('check_in_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_distance_meters', 8, 2)->nullable()->comment('Distance from branch center');
            $table->boolean('check_in_within_geofence')->default(false);
            $table->string('check_in_ip', 45)->nullable();
            $table->string('check_in_device')->nullable();

            // --- Check-out ---
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_distance_meters', 8, 2)->nullable();
            $table->boolean('check_out_within_geofence')->default(false);

            // --- Status & Calculation ---
            $table->enum('status', [
                'present', 'late', 'absent', 'on_leave',
                'holiday', 'remote', 'half_day',
            ])->default('present');
            $table->unsignedSmallInteger('delay_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->unsignedSmallInteger('worked_minutes')->default(0);

            // --- Financial Impact ---
            $table->decimal('cost_per_minute', 8, 4)->default(0)->comment('Snapshot from user salary at time of log');
            $table->decimal('delay_cost', 10, 2)->default(0)->comment('delay_minutes × cost_per_minute');
            $table->decimal('early_leave_cost', 10, 2)->default(0);
            $table->decimal('overtime_value', 10, 2)->default(0);

            // --- Notes & Approval ---
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_manual_entry')->default(false);

            $table->timestamps();

            // --- Indexes ---
            $table->unique(['user_id', 'attendance_date']);
            $table->index(['branch_id', 'attendance_date']);
            $table->index(['status', 'attendance_date']);
            $table->index('attendance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
