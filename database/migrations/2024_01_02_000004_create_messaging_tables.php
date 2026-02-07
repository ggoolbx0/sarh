<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Conversations (Internal Chat) ---
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->enum('type', ['direct', 'group', 'broadcast'])->default('direct');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_muted')->default(false);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });

        // --- Messages ---
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->enum('type', ['text', 'file', 'image', 'system'])->default('text');
            $table->string('attachment_path')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        // --- Circulars (Company-wide Announcements) ---
        Schema::create('circulars', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en');
            $table->longText('body_ar');
            $table->longText('body_en');
            $table->enum('priority', ['normal', 'important', 'urgent'])->default('normal');
            $table->enum('target_scope', ['all', 'branch', 'department', 'role'])->default('all');
            $table->foreignId('target_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('target_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('target_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('requires_acknowledgment')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('circular_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circular_id')->constrained('circulars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(['circular_id', 'user_id']);
        });

        // --- Performance Alerts (Automated) ---
        Schema::create('performance_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('alert_type')->comment('e.g. excessive_delay, streak_broken, absence_pattern');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title_ar');
            $table->string('title_en');
            $table->text('message_ar');
            $table->text('message_en');
            $table->json('trigger_data')->nullable()->comment('Context data that triggered the alert');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['alert_type', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_alerts');
        Schema::dropIfExists('circular_acknowledgments');
        Schema::dropIfExists('circulars');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
