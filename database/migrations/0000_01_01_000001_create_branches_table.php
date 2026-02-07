<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('code')->unique()->comment('Short code e.g. RYD-HQ');
            $table->text('address_ar')->nullable();
            $table->text('address_en')->nullable();
            $table->string('city_ar')->nullable();
            $table->string('city_en')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // --- Geofencing (Leaflet.js) ---
            $table->decimal('latitude', 10, 7)->comment('Branch center latitude');
            $table->decimal('longitude', 10, 7)->comment('Branch center longitude');
            $table->unsignedSmallInteger('geofence_radius')->default(17)->comment('Radius in meters (default 17m)');

            // --- Operations ---
            $table->time('default_shift_start')->default('08:00');
            $table->time('default_shift_end')->default('17:00');
            $table->unsignedSmallInteger('grace_period_minutes')->default(5)->comment('Minutes before late');
            $table->boolean('is_active')->default(true);

            // --- Financial Summary (cached) ---
            $table->decimal('monthly_salary_budget', 14, 2)->default(0);
            $table->decimal('monthly_delay_losses', 14, 2)->default(0)->comment('Auto-calculated');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
