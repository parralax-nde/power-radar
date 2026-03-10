<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\PowerReading;
use App\Models\PowerUnit;
use App\Models\UnitTransaction;
use App\Services\MqttServiceFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct(private MqttServiceFactory $mqttFactory) {}

    /**
     * Admin login form (only shown when ADMIN_PASSWORD is set).
     */
    public function loginForm()
    {
        return view('admin.login');
    }

    /**
     * Admin dashboard – system overview.
     */
    public function dashboard()
    {
        $devices       = Device::with(['latestReading', 'powerUnit'])->latest()->get();
        $totalDevices  = $devices->count();
        $activeDevices = $devices->where('active', true)->count();
        $cutoffDevices = $devices->filter(fn ($d) => $d->is_cutoff)->count();
        $shellyCount   = $devices->where('device_type', Device::TYPE_SHELLY)->count();
        $tasmotaCount  = $devices->where('device_type', Device::TYPE_TASMOTA)->count();

        $totalPowerW    = $devices->sum(fn ($d) => $d->latestReading?->power_w ?? 0);
        $totalBalanceKwh = $devices->sum(fn ($d) => $d->powerUnit?->balance_kwh ?? 0);

        $recentTransactions = UnitTransaction::with('device')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('admin.dashboard', compact(
            'devices',
            'totalDevices',
            'activeDevices',
            'cutoffDevices',
            'shellyCount',
            'tasmotaCount',
            'totalPowerW',
            'totalBalanceKwh',
            'recentTransactions',
        ));
    }

    /**
     * Show the system onboarding form.
     */
    public function onboard()
    {
        return view('admin.onboard');
    }

    /**
     * Process system onboarding (create device).
     */
    public function onboardStore(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:100',
            'device_type'         => 'required|in:shelly,tasmota',
            'shelly_id'           => 'required|string|max:100|unique:devices',
            'ip_address'          => 'nullable|ip',
            'mqtt_host'           => 'required|string|max:255',
            'mqtt_port'           => 'required|integer|min:1|max:65535',
            'mqtt_username'       => 'nullable|string|max:100',
            'mqtt_password'       => 'nullable|string|max:255',
            'mqtt_prefix'         => 'nullable|string|max:100',
            'cutoff_units'        => 'required|numeric|min:0',
            'auto_cutoff_enabled' => 'boolean',
            'initial_balance_kwh' => 'nullable|numeric|min:0',
        ]);

        $data['auto_cutoff_enabled'] = $request->boolean('auto_cutoff_enabled');
        $data['mqtt_prefix']         = $data['mqtt_prefix'] ?? 'shellyplus1pm';

        $device = Device::create($data);

        // Create initial power unit record
        $initialBalance = (float) ($data['initial_balance_kwh'] ?? 0);
        $unit = PowerUnit::create([
            'device_id'            => $device->id,
            'balance_kwh'          => $initialBalance,
            'total_purchased_kwh'  => $initialBalance,
        ]);

        // Log initial top-up if balance > 0
        if ($initialBalance > 0) {
            UnitTransaction::create([
                'device_id'         => $device->id,
                'type'              => 'purchase',
                'amount_kwh'        => $initialBalance,
                'balance_after_kwh' => $initialBalance,
                'note'              => 'Initial balance on onboarding',
            ]);
        }

        Log::info("Admin onboarded new device: {$device->name} ({$device->shelly_id}) type={$device->device_type}");

        return redirect()->route('admin.dashboard')
            ->with('success', "System \"{$device->name}\" onboarded successfully ({$device->deviceTypeLabel()}).");
    }

    /**
     * Show edit form for a device (admin version).
     */
    public function editDevice(Device $device)
    {
        return view('admin.edit-device', compact('device'));
    }

    /**
     * Update device settings (admin version).
     */
    public function updateDevice(Request $request, Device $device)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:100',
            'device_type'         => 'required|in:shelly,tasmota',
            'shelly_id'           => "required|string|max:100|unique:devices,shelly_id,{$device->id}",
            'ip_address'          => 'nullable|ip',
            'mqtt_host'           => 'required|string|max:255',
            'mqtt_port'           => 'required|integer|min:1|max:65535',
            'mqtt_username'       => 'nullable|string|max:100',
            'mqtt_password'       => 'nullable|string|max:255',
            'mqtt_prefix'         => 'nullable|string|max:100',
            'cutoff_units'        => 'required|numeric|min:0',
            'auto_cutoff_enabled' => 'boolean',
            'active'              => 'boolean',
        ]);

        $data['auto_cutoff_enabled'] = $request->boolean('auto_cutoff_enabled');
        $data['active']              = $request->boolean('active');
        $data['mqtt_prefix']         = $data['mqtt_prefix'] ?? 'shellyplus1pm';

        $device->update($data);

        return redirect()->route('admin.dashboard')
            ->with('success', "Device \"{$device->name}\" updated.");
    }

    /**
     * Delete a device (admin version).
     */
    public function destroyDevice(Device $device)
    {
        $name = $device->name;
        $device->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', "Device \"{$name}\" removed.");
    }

    /**
     * Toggle relay for a device.
     */
    public function toggleRelay(Request $request, Device $device)
    {
        $on = $request->boolean('on', !$device->relay_state);

        if ($on && $device->is_cutoff) {
            return back()->with('error', 'Cannot turn on: units are exhausted. Top up first.');
        }

        try {
            $this->mqttFactory->make($device)->sendRelayCommand($device, $on);
            return back()->with('success', 'Relay ' . ($on ? 'turned ON' : 'turned OFF') . '.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not reach device: ' . $e->getMessage());
        }
    }
}
