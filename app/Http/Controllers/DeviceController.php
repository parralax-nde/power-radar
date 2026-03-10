<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PowerUnit;
use App\Services\ShellyMqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceController extends Controller
{
    public function __construct(private ShellyMqttService $mqtt) {}

    public function index()
    {
        $devices = Device::with(['latestReading', 'powerUnit'])->latest()->get();
        return view('devices.index', compact('devices'));
    }

    public function create()
    {
        return view('devices.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:100',
            'shelly_id'            => 'required|string|max:100|unique:devices',
            'ip_address'           => 'nullable|ip',
            'mqtt_host'            => 'required|string|max:255',
            'mqtt_port'            => 'required|integer|min:1|max:65535',
            'mqtt_username'        => 'nullable|string|max:100',
            'mqtt_password'        => 'nullable|string|max:255',
            'mqtt_prefix'          => 'required|string|max:100',
            'cutoff_units'         => 'required|numeric|min:0',
            'auto_cutoff_enabled'  => 'boolean',
        ]);

        $data['auto_cutoff_enabled'] = $request->boolean('auto_cutoff_enabled');

        $device = Device::create($data);

        // Create initial power unit record
        PowerUnit::create([
            'device_id'   => $device->id,
            'balance_kwh' => 0,
        ]);

        return redirect()->route('devices.show', $device)
            ->with('success', 'Device added successfully.');
    }

    public function show(Device $device)
    {
        $device->load(['powerUnit', 'latestReading']);

        $chartReadings = $device->powerReadings()
            ->orderByDesc('recorded_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $transactions = $device->unitTransactions()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('devices.show', compact('device', 'chartReadings', 'transactions'));
    }

    public function edit(Device $device)
    {
        return view('devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:100',
            'shelly_id'            => "required|string|max:100|unique:devices,shelly_id,{$device->id}",
            'ip_address'           => 'nullable|ip',
            'mqtt_host'            => 'required|string|max:255',
            'mqtt_port'            => 'required|integer|min:1|max:65535',
            'mqtt_username'        => 'nullable|string|max:100',
            'mqtt_password'        => 'nullable|string|max:255',
            'mqtt_prefix'          => 'required|string|max:100',
            'cutoff_units'         => 'required|numeric|min:0',
            'auto_cutoff_enabled'  => 'boolean',
            'active'               => 'boolean',
        ]);

        $data['auto_cutoff_enabled'] = $request->boolean('auto_cutoff_enabled');
        $data['active']              = $request->boolean('active');

        $device->update($data);

        return redirect()->route('devices.show', $device)
            ->with('success', 'Device updated.');
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')
            ->with('success', 'Device removed.');
    }

    /**
     * Toggle the relay on/off via MQTT.
     */
    public function toggleRelay(Request $request, Device $device)
    {
        $on = $request->boolean('on', !$device->relay_state);

        // Don't allow turning on if units are exhausted
        if ($on && $device->is_cutoff) {
            return back()->with('error', 'Cannot turn on: units are exhausted. Please top up first.');
        }

        try {
            $this->mqtt->sendRelayCommand($device, $on);
            return back()->with('success', 'Relay ' . ($on ? 'turned ON' : 'turned OFF') . ' successfully.');
        } catch (\Throwable $e) {
            Log::error('Toggle relay failed: ' . $e->getMessage());
            return back()->with('error', 'Could not reach device: ' . $e->getMessage());
        }
    }

    /**
     * Manually poll the device status via MQTT.
     */
    public function pollStatus(Device $device)
    {
        try {
            $result = $this->mqtt->pollStatus($device);
            if (!$result) {
                return back()->with('error', 'No response from device.');
            }
            return back()->with('success', "Reading recorded: {$result['power_w']} W");
        } catch (\Throwable $e) {
            Log::error('Poll status failed: ' . $e->getMessage());
            return back()->with('error', 'Poll failed: ' . $e->getMessage());
        }
    }
}
