<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Psychological Trap Registry ---
        Schema::create('traps', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('trap_code')->unique()->comment('SALARY_PEEK, PRIVILEGE_ESCALATION, etc.');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedTinyInteger('risk_weight')->default(5)->comment('1-10 severity multiplier');
            $table->enum('fake_response_type', ['success', 'error', 'warning'])->default('success');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traps');
    }
};
