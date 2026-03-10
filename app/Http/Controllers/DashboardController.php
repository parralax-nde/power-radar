<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PowerReading;
use App\Models\UnitTransaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $devices = Device::with(['latestReading', 'powerUnit'])
            ->where('active', true)
            ->get();

        $totalPowerW = $devices->sum(fn ($d) => (float) ($d->latestReading?->power_w ?? 0));

        $recentReadings = PowerReading::with('device')
            ->orderByDesc('recorded_at')
            ->limit(20)
            ->get();

        $recentTransactions = UnitTransaction::with('device')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'devices',
            'totalPowerW',
            'recentReadings',
            'recentTransactions',
        ));
    }

    /**
     * API: return live data for a single device (polled by JS).
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
            'device'        => [
                'id'           => $device->id,
                'name'         => $device->name,
                'relay_state'  => $device->relay_state,
                'last_seen_at' => $device->last_seen_at?->diffForHumans(),
                'is_cutoff'    => $device->is_cutoff,
            ],
            'reading'       => $reading ? [
                'power_w'      => $reading->power_w,
                'voltage_v'    => $reading->voltage_v,
                'current_a'    => $reading->current_a,
                'energy_kwh'   => $reading->energy_kwh,
                'temperature_c'=> $reading->temperature_c,
                'pf'           => $reading->pf,
                'recorded_at'  => $reading->recorded_at?->diffForHumans(),
            ] : null,
            'unit'          => $device->powerUnit ? [
                'balance_kwh'     => round((float) $device->powerUnit->balance_kwh, 4),
                'is_cutoff'       => $device->powerUnit->is_cutoff,
                'balance_percent' => $device->powerUnit->balance_percent,
                'total_consumed'  => round((float) $device->powerUnit->total_consumed_kwh, 4),
            ] : null,
            'chart'         => [
                'labels' => $chartReadings->pluck('recorded_at')->map->diffForHumans()->toArray(),
                'power'  => $chartReadings->pluck('power_w')->map(fn ($v) => round((float) $v, 2))->toArray(),
            ],
        ]);
    }
}
