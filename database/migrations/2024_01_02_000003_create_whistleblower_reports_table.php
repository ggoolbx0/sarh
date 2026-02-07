<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Whistleblower Reports (Encrypted & Anonymous) ---
        Schema::create('whistleblower_reports', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique()->comment('Anonymous tracking code');
            $table->text('encrypted_content')->comment('AES-256 encrypted report body');
            $table->string('category')->comment('e.g. fraud, harassment, corruption, safety');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['new', 'under_review', 'investigating', 'resolved', 'dismissed'])->default('new');

            // Anonymous — no direct FK to users
            $table->string('anonymous_token')->unique()->comment('Hashed token for follow-up');
            $table->text('encrypted_evidence_paths')->nullable()->comment('Encrypted file paths');

            // --- Investigation ---
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('investigator_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_outcome')->nullable();

            $table->timestamps();
            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whistleblower_reports');
    }
};
