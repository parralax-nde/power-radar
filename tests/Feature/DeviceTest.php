<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\PowerUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_index_loads(): void
    {
        $this->get(route('devices.index'))->assertStatus(200);
    }

    public function test_device_create_form_loads(): void
    {
        $this->get(route('devices.create'))->assertStatus(200);
        $this->get(route('devices.create'))->assertSee('Add New Device');
    }

    public function test_device_can_be_created(): void
    {
        $response = $this->post(route('devices.store'), [
            'name'                => 'Kitchen Socket',
            'shelly_id'           => 'shellypmmini3-KITCHEN01',
            'ip_address'          => '192.168.1.55',
            'mqtt_host'           => 'localhost',
            'mqtt_port'           => 1883,
            'mqtt_prefix'         => 'shellypmmini3',
            'cutoff_units'        => 0,
            'auto_cutoff_enabled' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('devices', ['shelly_id' => 'shellypmmini3-KITCHEN01']);
        $this->assertDatabaseHas('power_units', [
            'device_id' => Device::where('shelly_id', 'shellypmmini3-KITCHEN01')->first()->id,
        ]);
    }

    public function test_device_store_validates_required_fields(): void
    {
        $response = $this->post(route('devices.store'), []);
        $response->assertSessionHasErrors(['name', 'shelly_id', 'mqtt_host', 'mqtt_port', 'mqtt_prefix', 'cutoff_units']);
    }

    public function test_device_shelly_id_must_be_unique(): void
    {
        $this->createDevice(['shelly_id' => 'shellypmmini3-DUPE01']);

        $response = $this->post(route('devices.store'), [
            'name'       => 'Another Device',
            'shelly_id'  => 'shellypmmini3-DUPE01',
            'mqtt_host'  => 'localhost',
            'mqtt_port'  => 1883,
            'mqtt_prefix'=> 'shellypmmini3',
            'cutoff_units'=> 0,
        ]);
        $response->assertSessionHasErrors('shelly_id');
    }

    public function test_device_show_page_loads(): void
    {
        $device = $this->createDevice();

        $this->get(route('devices.show', $device))
             ->assertStatus(200)
             ->assertSee($device->name);
    }

    public function test_device_can_be_updated(): void
    {
        $device = $this->createDevice();

        $this->put(route('devices.update', $device), [
            'name'                => 'Updated Name',
            'shelly_id'           => $device->shelly_id,
            'mqtt_host'           => 'broker.example.com',
            'mqtt_port'           => 1883,
            'mqtt_prefix'         => 'shellypmmini3',
            'cutoff_units'        => 5,
            'auto_cutoff_enabled' => 1,
            'active'              => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('devices', [
            'id'       => $device->id,
            'name'     => 'Updated Name',
            'mqtt_host'=> 'broker.example.com',
        ]);
    }

    public function test_device_can_be_deleted(): void
    {
        $device = $this->createDevice();

        $this->delete(route('devices.destroy', $device))->assertRedirect();
        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }

    public function test_topup_form_loads(): void
    {
        $device = $this->createDevice();

        $this->get(route('units.create', $device))
             ->assertStatus(200)
             ->assertSee('Top Up Units');
    }

    public function test_units_can_be_topped_up(): void
    {
        $device = $this->createDevice();
        PowerUnit::create(['device_id' => $device->id, 'balance_kwh' => 0]);

        $this->post(route('units.store', $device), [
            'kwh_amount' => 10.5,
            'note'       => 'Monthly recharge',
        ])->assertRedirect(route('devices.show', $device));

        $this->assertDatabaseHas('power_units', [
            'device_id'   => $device->id,
            'balance_kwh' => 10.5,
        ]);
        $this->assertDatabaseHas('unit_transactions', [
            'device_id'  => $device->id,
            'type'       => 'purchase',
            'amount_kwh' => 10.5,
        ]);
    }

    public function test_topup_validates_amount(): void
    {
        $device = $this->createDevice();

        $this->post(route('units.store', $device), ['kwh_amount' => 0])
             ->assertSessionHasErrors('kwh_amount');

        $this->post(route('units.store', $device), ['kwh_amount' => -1])
             ->assertSessionHasErrors('kwh_amount');
    }

    public function test_api_live_endpoint_returns_json(): void
    {
        $device = $this->createDevice();

        $this->get(route('api.live', $device))
             ->assertStatus(200)
             ->assertJsonStructure(['device', 'reading', 'unit', 'chart']);
    }

    private function createDevice(array $overrides = []): Device
    {
        return Device::create(array_merge([
            'name'                => 'Test Device',
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
