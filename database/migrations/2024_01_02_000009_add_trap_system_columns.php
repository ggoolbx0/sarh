<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add risk_score to users for logarithmic risk tracking
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('risk_score')->default(0)->after('is_trap_target');
        });

        // Add trap_id FK to trap_interactions for registry linkage
        Schema::table('trap_interactions', function (Blueprint $table) {
            $table->foreignId('trap_id')->nullable()->after('user_id')->constrained('traps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trap_interactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trap_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('risk_score');
        });
    }
};
