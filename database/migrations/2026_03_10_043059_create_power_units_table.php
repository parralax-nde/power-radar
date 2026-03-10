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
        Schema::create('power_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('balance_kwh', 12, 6)->default(0)->comment('Available units in kWh');
            $table->decimal('total_purchased_kwh', 12, 6)->default(0);
            $table->decimal('total_consumed_kwh', 12, 6)->default(0);
            $table->decimal('energy_kwh_at_last_reset', 12, 6)->default(0)->comment('Device cumulative kWh when units were last reset/topped up');
            $table->boolean('is_cutoff')->default(false)->comment('Device is currently cut off due to low units');
            $table->timestamp('cutoff_at')->nullable();
            $table->timestamps();
            $table->unique('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('power_units');
    }
};
