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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shelly_id')->unique()->comment('Shelly device ID, e.g. shellypmmini3-AABBCCDDEEFF');
            $table->string('ip_address')->nullable()->comment('Local IP of the Shelly device');
            $table->string('mqtt_host')->default('localhost');
            $table->integer('mqtt_port')->default(1883);
            $table->string('mqtt_username')->nullable();
            $table->string('mqtt_password')->nullable();
            $table->string('mqtt_prefix')->default('shellyplus1pm')->comment('MQTT topic prefix');
            $table->decimal('cutoff_units', 10, 4)->default(0)->comment('Auto power-off threshold in kWh units');
            $table->boolean('relay_state')->default(false)->comment('Current relay on/off state');
            $table->boolean('auto_cutoff_enabled')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
