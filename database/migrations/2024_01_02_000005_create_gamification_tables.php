<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Badges ---
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('icon')->nullable()->comment('Badge icon path or class');
            $table->string('color', 20)->default('#10b981');
            $table->enum('category', ['attendance', 'finance', 'performance', 'special'])->default('attendance');
            $table->unsignedInteger('points_reward')->default(0);
            $table->json('criteria')->nullable()->comment('JSON rules for auto-awarding');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // --- User ↔ Badge Pivot ---
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->timestamp('awarded_at');
            $table->string('awarded_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'badge_id']);
        });

        // --- Points Transactions Ledger ---
        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['earned', 'deducted', 'bonus', 'redeemed'])->default('earned');
            $table->integer('amount')->comment('Positive for earned, signed');
            $table->unsignedInteger('balance_after')->default(0);
            $table->string('source')->comment('e.g. on_time_checkin, badge_reward, delay_penalty');
            $table->morphs('sourceable');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_transactions');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};
