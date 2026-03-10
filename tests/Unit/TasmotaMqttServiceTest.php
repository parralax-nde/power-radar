<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\PowerUnit;
use App\Services\TasmotaMqttService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TasmotaMqttServiceTest extends TestCase
{
    use RefreshDatabase;

    private TasmotaMqttService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TasmotaMqttService();
    }

    public function test_store_reading_parses_tasmota_sensor_format(): void
    {
        $device = $this->makeDevice();

        $payload = [
            'ENERGY' => [
                'Power'         => 142.5,
                'Voltage'       => 231.0,
                'Current'       => 0.617,
                'Factor'        => 0.92,
                'Today'         => 1.234,
                'Yesterday'     => 2.456,
                'Total'         => 45.678,
            ],
        ];

        $result = $this->service->storeReading($device, $payload);

        $this->assertEquals(142.5, $result['power_w']);
        $this->assertEquals(45.678, $result['energy_kwh']);

        $this->assertDatabaseHas('power_readings', [
            'device_id' => $device->id,
            'power_w'   => 142.5,
            'voltage_v' => 231.0,
            'current_a' => 0.617,
            'pf'        => 0.92,
            'energy_kwh'=> 45.678,
        ]);
    }

    public function test_store_reading_handles_nested_status_sns_format(): void
    {
        $device = $this->makeDevice();

        // Tasmota Status 8 response format
        $payload = [
            'StatusSNS' => [
                'ENERGY' => [
                    'Power'   => 75.0,
                    'Voltage' => 220.0,
                    'Current' => 0.34,
                    'Total'   => 10.5,
                ],
            ],
        ];

        $result = $this->service->storeReading($device, $payload);

        $this->assertEquals(75.0, $result['power_w']);
        $this->assertEquals(10.5, $result['energy_kwh']);
    }

    public function test_units_deducted_on_tasmota_reading(): void
    {
        $device = $this->makeDevice();
        PowerUnit::create([
            'device_id'                => $device->id,
            'balance_kwh'              => 5.0,
            'total_purchased_kwh'      => 5.0,
            'total_consumed_kwh'       => 0,
            'energy_kwh_at_last_reset' => 0,
            'is_cutoff'                => false,
        ]);

        $this->service->storeReading($device, [
            'ENERGY' => ['Power' => 100, 'Voltage' => 220, 'Current' => 0.45, 'Total' => 2.0],
        ]);

        $unit = PowerUnit::where('device_id', $device->id)->first();
        $this->assertLessThan(5.0, (float) $unit->balance_kwh);
    }

    public function test_relay_state_set_from_power_field(): void
    {
        $device = $this->makeDevice();

        // With POWER = ON
        $result = $this->service->storeReading($device, [
            'ENERGY' => ['Power' => 0, 'Voltage' => 230, 'Current' => 0, 'Total' => 0],
            'POWER'  => 'ON',
        ]);
        $this->assertTrue($result['relay_on']);

        // With POWER = OFF
        $result = $this->service->storeReading($device, [
            'ENERGY' => ['Power' => 0, 'Voltage' => 230, 'Current' => 0, 'Total' => 0],
            'POWER'  => 'OFF',
        ]);
        $this->assertFalse($result['relay_on']);
    }

    public function test_device_tasmota_status_topic(): void
    {
        $device = $this->makeDevice();
        $this->assertEquals(
            "tele/{$device->shelly_id}/SENSOR",
            $device->statusTopic()
        );
    }

    public function test_device_tasmota_rpc_topic(): void
    {
        $device = $this->makeDevice();
        $this->assertEquals(
            "cmnd/{$device->shelly_id}/Power",
            $device->rpcTopic()
        );
    }

    public function test_device_is_tasmota_returns_true(): void
    {
        $device = $this->makeDevice();
        $this->assertTrue($device->isTasmota());
    }

    public function test_device_type_label_for_tasmota(): void
    {
        $device = $this->makeDevice();
        $this->assertEquals('Tasmota / Athom', $device->deviceTypeLabel());
    }

    private function makeDevice(array $overrides = []): Device
    {
        return Device::create(array_merge([
            'name'                => 'Tasmota Test Device',
            'device_type'         => 'tasmota',
            'shelly_id'           => 'tasmota-' . uniqid(),
            'mqtt_host'           => 'localhost',
            'mqtt_port'           => 1883,
            'mqtt_prefix'         => 'tasmota',
            'cutoff_units'        => 0,
            'relay_state'         => true,
            'auto_cutoff_enabled' => true,
            'active'              => true,
        ], $overrides));
    }
}
