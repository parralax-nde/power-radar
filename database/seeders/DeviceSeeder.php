<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\PowerReading;
use App\Models\PowerUnit;
use App\Models\UnitTransaction;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $device = Device::create([
            'name'                => 'Living Room Socket',
            'shelly_id'           => 'shellypmmini3-AABBCCDD1122',
            'ip_address'          => '192.168.1.100',
            'mqtt_host'           => 'localhost',
            'mqtt_port'           => 1883,
            'mqtt_prefix'         => 'shellypmmini3',
            'cutoff_units'        => 0,
            'relay_state'         => true,
            'auto_cutoff_enabled' => true,
            'active'              => true,
            'last_seen_at'        => now(),
        ]);

        $unit = PowerUnit::create([
            'device_id'                => $device->id,
            'balance_kwh'              => 12.5,
            'total_purchased_kwh'      => 50.0,
            'total_consumed_kwh'       => 37.5,
            'energy_kwh_at_last_reset' => 0,
            'is_cutoff'                => false,
        ]);

        UnitTransaction::create([
            'device_id'         => $device->id,
            'type'              => 'purchase',
            'amount_kwh'        => 50.0,
            'balance_after_kwh' => 50.0,
            'note'              => 'Initial top-up',
        ]);

        UnitTransaction::create([
            'device_id'         => $device->id,
            'type'              => 'consumption',
            'amount_kwh'        => -37.5,
            'balance_after_kwh' => 12.5,
            'note'              => 'Auto-deducted from meter reading',
        ]);

        // Fake some readings
        $now = now();
        for ($i = 20; $i >= 0; $i--) {
            PowerReading::create([
                'device_id'    => $device->id,
                'power_w'      => round(rand(60, 220) + (rand(0, 99) / 100), 2),
                'voltage_v'    => round(220 + rand(-5, 5) + rand(0, 9) / 10, 1),
                'current_a'    => round(rand(3, 10) / 10, 3),
                'energy_kwh'   => round(37.5 + ($i * 0.05), 6),
                'pf'           => round(0.95 + rand(-5, 5) / 100, 4),
                'temperature_c'=> round(45 + rand(0, 10), 1),
                'recorded_at'  => $now->copy()->subMinutes($i * 5),
            ]);
        }

        // Second device (cut off)
        $device2 = Device::create([
            'name'                => 'Garage Charger',
            'shelly_id'           => 'shellypmmini3-FFEE221100',
            'ip_address'          => '192.168.1.101',
            'mqtt_host'           => 'localhost',
            'mqtt_port'           => 1883,
            'mqtt_prefix'         => 'shellypmmini3',
            'cutoff_units'        => 0,
            'relay_state'         => false,
            'auto_cutoff_enabled' => true,
            'active'              => true,
            'last_seen_at'        => now()->subMinutes(15),
        ]);

        PowerUnit::create([
            'device_id'                => $device2->id,
            'balance_kwh'              => 0,
            'total_purchased_kwh'      => 10.0,
            'total_consumed_kwh'       => 10.0,
            'energy_kwh_at_last_reset' => 0,
            'is_cutoff'                => true,
            'cutoff_at'                => now()->subMinutes(15),
        ]);

        PowerReading::create([
            'device_id'    => $device2->id,
            'power_w'      => 0,
            'voltage_v'    => 0,
            'current_a'    => 0,
            'energy_kwh'   => 10.0,
            'recorded_at'  => now()->subMinutes(15),
        ]);
    }
}
