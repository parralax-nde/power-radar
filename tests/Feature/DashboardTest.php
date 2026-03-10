<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\PowerReading;
use App\Models\PowerUnit;
use App\Models\UnitTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Power Radar');
    }

    public function test_dashboard_shows_devices(): void
    {
        $device = $this->createDevice();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee($device->name);
        $response->assertSee($device->shelly_id);
    }

    public function test_dashboard_shows_cutoff_warning(): void
    {
        $device = $this->createDevice();
        PowerUnit::create([
            'device_id'   => $device->id,
            'balance_kwh' => 0,
            'is_cutoff'   => true,
            'cutoff_at'   => now(),
        ]);
        $device->update(['relay_state' => false]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Cut Off');
    }

    private function createDevice(array $overrides = []): Device
    {
        return Device::create(array_merge([
            'name'                => 'Test Device',
            'shelly_id'           => 'shellypmmini3-TEST001',
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
