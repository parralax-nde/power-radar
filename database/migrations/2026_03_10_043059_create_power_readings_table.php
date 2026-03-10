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
        Schema::create('power_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('power_w', 10, 2)->default(0)->comment('Current power in Watts');
            $table->decimal('voltage_v', 8, 2)->nullable()->comment('Voltage in Volts');
            $table->decimal('current_a', 8, 4)->nullable()->comment('Current in Amperes');
            $table->decimal('energy_kwh', 12, 6)->default(0)->comment('Cumulative energy in kWh from device');
            $table->decimal('pf', 5, 4)->nullable()->comment('Power factor');
            $table->decimal('temperature_c', 6, 2)->nullable()->comment('Device temperature in Celsius');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
            $table->index(['device_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('power_readings');
    }
};
