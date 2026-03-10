<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\PowerUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_loads(): void
    {
        $this->get(route('admin.dashboard'))->assertStatus(200);
    }

    public function test_admin_dashboard_shows_all_devices(): void
    {
        $this->createDevice(['device_type' => 'shelly', 'name' => 'Shelly Device']);
        $this->createDevice(['device_type' => 'tasmota', 'name' => 'Tasmota Device']);

        $this->get(route('admin.dashboard'))
             ->assertStatus(200)
             ->assertSee('Shelly Device')
             ->assertSee('Tasmota Device');
    }

    public function test_admin_onboard_form_loads(): void
    {
        $this->get(route('admin.onboard'))
             ->assertStatus(200)
             ->assertSee('Onboard New System');
    }

    public function test_admin_can_onboard_shelly_device(): void
    {
        $response = $this->post(route('admin.onboard.store'), [
            'name'                => 'New Shelly',
            'device_type'         => 'shelly',
            'shelly_id'           => 'shellypmmini3-TEST01',
            'mqtt_host'           => 'localhost',
            'mqtt_port'           => 1883,
            'mqtt_prefix'         => 'shellypmmini3',
            'cutoff_units'        => 0,
            'auto_cutoff_enabled' => 1,
            'initial_balance_kwh' => 10,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseHas('devices', [
            'shelly_id'   => 'shellypmmini3-TEST01',
            'device_type' => 'shelly',
        ]);
        $this->assertDatabaseHas('power_units', [
            'balance_kwh' => 10,
        ]);
        $this->assertDatabaseHas('unit_transactions', [
            'type'       => 'purchase',
            'amount_kwh' => 10,
            'note'       => 'Initial balance on onboarding',
        ]);
    }

    public function test_admin_can_onboard_tasmota_device(): void
    {
        $response = $this->post(route('admin.onboard.store'), [
            'name'                => 'Athom PM',
            'device_type'         => 'tasmota',
            'shelly_id'           => 'tasmota-athom-pm1',
            'mqtt_host'           => 'localhost',
            'mqtt_port'           => 1883,
            'cutoff_units'        => 0,
            'auto_cutoff_enabled' => 1,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseHas('devices', [
            'shelly_id'   => 'tasmota-athom-pm1',
            'device_type' => 'tasmota',
        ]);
    }

    public function test_admin_onboard_validates_device_type(): void
    {
        $response = $this->post(route('admin.onboard.store'), [
            'name'         => 'Bad Device',
            'device_type'  => 'invalid_type',
            'shelly_id'    => 'test-device',
            'mqtt_host'    => 'localhost',
            'mqtt_port'    => 1883,
            'cutoff_units' => 0,
        ]);

        $response->assertSessionHasErrors('device_type');
    }

    public function test_admin_can_edit_device(): void
    {
        $device = $this->createDevice(['device_type' => 'shelly']);

        $this->get(route('admin.devices.edit', $device))
             ->assertStatus(200)
             ->assertSee($device->name);
    }

    public function test_admin_can_update_device(): void
    {
        $device = $this->createDevice(['device_type' => 'shelly']);

        $this->put(route('admin.devices.update', $device), [
            'name'                => 'Renamed Device',
            'device_type'         => 'tasmota',
            'shelly_id'           => $device->shelly_id,
            'mqtt_host'           => 'broker.local',
            'mqtt_port'           => 1883,
            'cutoff_units'        => 5,
            'auto_cutoff_enabled' => 1,
            'active'              => 1,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('devices', [
            'id'          => $device->id,
            'name'        => 'Renamed Device',
            'device_type' => 'tasmota',
            'mqtt_host'   => 'broker.local',
        ]);
    }

    public function test_admin_can_delete_device(): void
    {
        $device = $this->createDevice();

        $this->delete(route('admin.devices.destroy', $device))
             ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }

    public function test_admin_onboard_without_initial_balance_creates_zero_balance(): void
    {
        $this->post(route('admin.onboard.store'), [
            'name'                => 'No Balance Device',
            'device_type'         => 'shelly',
            'shelly_id'           => 'shellypmmini3-NOBALS',
            'mqtt_host'           => 'localhost',
            'mqtt_port'           => 1883,
            'cutoff_units'        => 0,
            'auto_cutoff_enabled' => 1,
        ])->assertRedirect();

        $device = Device::where('shelly_id', 'shellypmmini3-NOBALS')->first();
        $this->assertNotNull($device);
        $this->assertDatabaseHas('power_units', [
            'device_id'   => $device->id,
            'balance_kwh' => 0,
        ]);
        // No transaction should be created for zero initial balance
        $this->assertDatabaseMissing('unit_transactions', [
            'device_id' => $device->id,
            'note'      => 'Initial balance on onboarding',
        ]);
    }

    private function createDevice(array $overrides = []): Device
    {
        return Device::create(array_merge([
            'name'                => 'Admin Test Device',
            'device_type'         => 'shelly',
            'shelly_id'           => 'shellypmmini3-' . uniqid(),
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
