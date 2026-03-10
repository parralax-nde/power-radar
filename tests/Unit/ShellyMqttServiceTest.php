<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\PowerUnit;
use App\Services\ShellyMqttService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShellyMqttServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShellyMqttService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShellyMqttService();
    }

    public function test_store_reading_creates_power_reading(): void
    {
        $device = $this->makeDevice();
        PowerUnit::create([
            'device_id'                => $device->id,
            'balance_kwh'              => 10,
            'energy_kwh_at_last_reset' => 0,
        ]);

        $payload = [
            'apower'  => 142.5,
            'voltage' => 220.1,
            'current' => 0.648,
            'aenergy' => ['total' => 5000],   // 5 kWh
            'pf'      => 0.98,
            'temperature' => ['tC' => 43.5],
            'output'  => true,
        ];

        $result = $this->service->storeReading($device, $payload);

        $this->assertDatabaseHas('power_readings', [
            'device_id' => $device->id,
            'power_w'   => 142.50,
            'energy_kwh'=> 5.0,
        ]);

        $this->assertEquals(142.5, $result['power_w']);
        $this->assertTrue($result['relay_on']);
        $this->assertEquals(5.0, $result['energy_kwh']);
    }

    public function test_units_are_deducted_on_reading(): void
    {
        $device = $this->makeDevice();
        PowerUnit::create([
            'device_id'                => $device->id,
            'balance_kwh'              => 10.0,
            'total_consumed_kwh'       => 0,
            'energy_kwh_at_last_reset' => 0.0,
        ]);

        // Simulate 2 kWh consumed
        $payload = [
            'apower'  => 100,
            'aenergy' => ['total' => 2000],  // 2 kWh cumulative
            'output'  => true,
        ];

        $this->service->storeReading($device, $payload);

        $unit = PowerUnit::where('device_id', $device->id)->first();
        $this->assertEqualsWithDelta(8.0, (float) $unit->balance_kwh, 0.001);
    }

    public function test_auto_cutoff_triggers_when_balance_exhausted(): void
    {
        $device = $this->makeDevice(['auto_cutoff_enabled' => true]);
        PowerUnit::create([
            'device_id'                => $device->id,
            'balance_kwh'              => 0.5,
            'total_consumed_kwh'       => 0,
            'energy_kwh_at_last_reset' => 0.0,
        ]);

        // 1 kWh consumed – more than balance
        $payload = [
            'apower'  => 200,
            'aenergy' => ['total' => 1000],  // 1 kWh
            'output'  => true,
        ];

        // The service will try to send MQTT command; we just check DB state
        $this->service->updateUnitsAndCheckCutoff($device, 1.0);

        $unit = PowerUnit::where('device_id', $device->id)->first();
        $this->assertEquals(0.0, (float) $unit->balance_kwh);
        $this->assertTrue($unit->is_cutoff);
    }

    public function test_topup_increases_balance(): void
    {
        $device = $this->makeDevice();
        PowerUnit::create([
            'device_id'   => $device->id,
            'balance_kwh' => 2.0,
        ]);

        $unit = $this->service->topUp($device, 5.0, 'Test top-up');

        $this->assertEqualsWithDelta(7.0, (float) $unit->balance_kwh, 0.001);
        $this->assertDatabaseHas('unit_transactions', [
            'device_id'  => $device->id,
            'type'       => 'purchase',
            'amount_kwh' => 5.0,
        ]);
    }

    public function test_device_status_topic_is_correct(): void
    {
        $device = $this->makeDevice([
            'mqtt_prefix' => 'shellypmmini3',
            'shelly_id'   => 'shellypmmini3-AABBCC',
        ]);

        $this->assertEquals(
            'shellypmmini3/shellypmmini3-AABBCC/status/switch:0',
            $device->statusTopic()
        );
    }

    private function makeDevice(array $overrides = []): Device
    {
        return Device::create(array_merge([
            'name'                => 'Unit Test Device',
            'device_type'         => 'shelly',
            'shelly_id'           => 'shellypmmini3-UNIT' . uniqid(),
            'mqtt_host'           => 'localhost',
            'mqtt_port'           => 1883,
            'mqtt_prefix'         => 'shellypmmini3',
            'cutoff_units'        => 0,
            'relay_state'         => true,
            'auto_cutoff_enabled' => true,
            'active'              => true,
        ], $overrides));
    }
}
