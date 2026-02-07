<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Psychological Trap Interactions ---
        Schema::create('trap_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('trap_type')->comment('e.g. fake_approve_button, fake_transfer_link, dummy_salary_view');
            $table->string('trap_element')->comment('UI element identifier');
            $table->string('page_url');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('interaction_data')->nullable()->comment('Click coords, timing, etc.');
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('is_reviewed')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'trap_type']);
            $table->index(['risk_level', 'is_reviewed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trap_interactions');
    }
};
