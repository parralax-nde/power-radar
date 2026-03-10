<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PowerReading;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * Live data endpoint for a device, used by the front-end polling.
     */
    public function liveData(Device $device)
    {
        $device->load(['latestReading', 'powerUnit']);
        $reading = $device->latestReading;

        // Last 20 readings for chart
        $chartReadings = PowerReading::where('device_id', $device->id)
            ->orderByDesc('recorded_at')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'device'  => [
                'id'           => $device->id,
                'name'         => $device->name,
                'relay_state'  => $device->relay_state,
                'last_seen_at' => $device->last_seen_at?->diffForHumans(),
                'is_cutoff'    => $device->is_cutoff,
            ],
            'reading' => $reading ? [
                'power_w'      => round((float) $reading->power_w, 2),
                'voltage_v'    => $reading->voltage_v ? round((float) $reading->voltage_v, 1) : null,
                'current_a'    => $reading->current_a ? round((float) $reading->current_a, 3) : null,
                'energy_kwh'   => round((float) $reading->energy_kwh, 4),
                'temperature_c'=> $reading->temperature_c ? round((float) $reading->temperature_c, 1) : null,
                'pf'           => $reading->pf ? round((float) $reading->pf, 3) : null,
                'recorded_at'  => $reading->recorded_at?->diffForHumans(),
            ] : null,
            'unit'    => $device->powerUnit ? [
                'balance_kwh'     => round((float) $device->powerUnit->balance_kwh, 4),
                'is_cutoff'       => $device->powerUnit->is_cutoff,
                'balance_percent' => $device->powerUnit->balance_percent,
                'total_consumed'  => round((float) $device->powerUnit->total_consumed_kwh, 4),
                'total_purchased' => round((float) $device->powerUnit->total_purchased_kwh, 4),
            ] : null,
            'chart'   => [
                'labels' => $chartReadings->pluck('recorded_at')
                    ->map(fn ($dt) => $dt?->format('H:i:s'))
                    ->toArray(),
                'power'  => $chartReadings->pluck('power_w')
                    ->map(fn ($v) => round((float) $v, 2))
                    ->toArray(),
            ],
        ]);
    }
}
