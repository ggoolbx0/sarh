<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 5: Production performance indexes for high-volume tables.
     *
     * Optimizes:
     *  - attendance_logs: Financial aggregation queries (SUM delay_cost), employee performance
     *  - trap_interactions: Audit trail chronology, risk trajectory per user
     *  - audit_logs: User audit trail, action filtering
     */
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->index('delay_cost', 'idx_attendance_delay_cost');
            $table->index(['user_id', 'status'], 'idx_attendance_user_status');
            $table->index(['attendance_date', 'delay_cost'], 'idx_attendance_date_cost');
        });

        Schema::table('trap_interactions', function (Blueprint $table) {
            $table->index('trap_id', 'idx_trap_interactions_trap');
            $table->index('created_at', 'idx_trap_interactions_created');
            $table->index(['user_id', 'created_at'], 'idx_trap_interactions_user_time');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('user_id', 'idx_audit_logs_user');
            $table->index('action', 'idx_audit_logs_action');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_delay_cost');
            $table->dropIndex('idx_attendance_user_status');
            $table->dropIndex('idx_attendance_date_cost');
        });

        Schema::table('trap_interactions', function (Blueprint $table) {
            $table->dropIndex('idx_trap_interactions_trap');
            $table->dropIndex('idx_trap_interactions_created');
            $table->dropIndex('idx_trap_interactions_user_time');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_user');
            $table->dropIndex('idx_audit_logs_action');
        });
    }
};
