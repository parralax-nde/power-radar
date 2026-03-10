<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\MqttServiceFactory;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(private MqttServiceFactory $mqttFactory) {}

    /**
     * Show the top-up form.
     */
    public function create(Device $device)
    {
        $device->load(['powerUnit', 'unitTransactions' => fn ($q) => $q->orderByDesc('created_at')->limit(10)]);
        return view('units.create', compact('device'));
    }

    /**
     * Process a unit top-up.
     */
    public function store(Request $request, Device $device)
    {
        $data = $request->validate([
            'kwh_amount' => 'required|numeric|min:0.001|max:10000',
            'note'       => 'nullable|string|max:255',
        ]);

        $unit = $this->mqttFactory->make($device)->topUp($device, (float) $data['kwh_amount'], $data['note'] ?? '');

        return redirect()->route('devices.show', $device)
            ->with('success', sprintf(
                '%.4f kWh added. New balance: %.4f kWh.',
                $data['kwh_amount'],
                $unit->balance_kwh,
            ));
    }
}
