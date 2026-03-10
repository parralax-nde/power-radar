@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mt-4">

    {{-- Page header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-800 mb-1 gradient-text">Dashboard</h1>
            <p class="mb-0" style="color: var(--pr-text-muted); font-size: 0.875rem;">
                Real-time power monitoring for all your Shelly devices
            </p>
        </div>
        <a href="{{ route('devices.create') }}" class="btn btn-pr-primary">
            <i class="bi bi-plus-lg me-1"></i>Add Device
        </a>
    </div>

    {{-- Summary metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="icon-box icon-box-purple mx-auto mb-2">
                    <i class="bi bi-cpu"></i>
                </div>
                <div class="metric-value glow-purple">{{ $devices->count() }}</div>
                <div class="metric-label">Total Devices</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="icon-box icon-box-blue mx-auto mb-2">
                    <i class="bi bi-lightning-charge"></i>
                </div>
                <div class="metric-value glow-blue" id="total-power">{{ number_format($totalPowerW, 1) }}</div>
                <div class="metric-label">Total Watts</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="icon-box icon-box-green mx-auto mb-2">
                    <i class="bi bi-toggle-on"></i>
                </div>
                <div class="metric-value glow-green">{{ $devices->where('relay_state', true)->count() }}</div>
                <div class="metric-label">Relays ON</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="icon-box icon-box-red mx-auto mb-2">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="metric-value glow-red">{{ $devices->filter(fn($d) => $d->is_cutoff)->count() }}</div>
                <div class="metric-label">Cut Off</div>
            </div>
        </div>
    </div>

    {{-- Device cards --}}
    @if ($devices->isEmpty())
        <div class="pr-card text-center py-5">
            <div class="icon-box icon-box-purple mx-auto mb-3" style="width:72px;height:72px;font-size:2rem">
                <i class="bi bi-cpu"></i>
            </div>
            <h4 class="fw-700 mb-2">No devices yet</h4>
            <p style="color: var(--pr-text-muted);">Add your first Shelly 1PM Mini Gen3 to get started.</p>
            <a href="{{ route('devices.create') }}" class="btn btn-pr-primary">
                <i class="bi bi-plus-lg me-1"></i>Add Device
            </a>
        </div>
    @else
        <div class="row g-4">
            @foreach ($devices as $device)
                @php
                    $unit    = $device->powerUnit;
                    $reading = $device->latestReading;
                    $balance = $unit ? (float) $unit->balance_kwh : 0;
                    $percent = $unit ? $unit->balance_percent : 0;
                    $barColor = $percent > 40 ? 'var(--pr-success)' : ($percent > 15 ? 'var(--pr-warning)' : 'var(--pr-danger)');
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="pr-card h-100 d-flex flex-column" id="device-card-{{ $device->id }}">
                        {{-- Card header --}}
                        <div class="pr-card-header">
                            <div>
                                <div class="fw-700 fs-5 mb-1">{{ $device->name }}</div>
                                <div style="font-size:0.75rem; color:var(--pr-text-muted)">
                                    {{ $device->shelly_id }}
                                </div>
                            </div>
                            <div>
                                @if ($device->is_cutoff)
                                    <span class="status-pill status-off"><span class="dot"></span>Cut Off</span>
                                @elseif ($device->relay_state)
                                    <span class="status-pill status-on"><span class="dot"></span>ON</span>
                                @else
                                    <span class="status-pill status-off"><span class="dot"></span>OFF</span>
                                @endif
                            </div>
                        </div>

                        {{-- Live power reading --}}
                        <div class="d-flex align-items-end gap-3 mb-3">
                            <div>
                                <div style="font-size:0.7rem;color:var(--pr-text-muted);text-transform:uppercase;letter-spacing:.1em">Power</div>
                                <div class="fw-800 glow-blue" style="font-size:2.2rem;line-height:1"
                                     id="pw-{{ $device->id }}">
                                    {{ $reading ? number_format((float)$reading->power_w, 1) : '—' }}
                                    <span style="font-size:1rem;font-weight:500;color:var(--pr-text-muted)">W</span>
                                </div>
                            </div>
                            @if ($reading)
                                <div class="mb-1">
                                    <div style="font-size:0.7rem;color:var(--pr-text-muted)">Voltage</div>
                                    <div class="fw-600" style="font-size:.95rem" id="vv-{{ $device->id }}">
                                        {{ $reading->voltage_v ? number_format((float)$reading->voltage_v, 0).'V' : '—' }}
                                    </div>
                                </div>
                                <div class="mb-1">
                                    <div style="font-size:0.7rem;color:var(--pr-text-muted)">Current</div>
                                    <div class="fw-600" style="font-size:.95rem" id="ca-{{ $device->id }}">
                                        {{ $reading->current_a ? number_format((float)$reading->current_a, 2).'A' : '—' }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Units balance --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:0.75rem;color:var(--pr-text-muted)">Units Balance</span>
                                <span class="fw-600" style="font-size:0.8rem" id="bal-{{ $device->id }}">
                                    {{ number_format($balance, 3) }} kWh
                                </span>
                            </div>
                            <div class="pr-progress">
                                <div class="pr-progress-bar" id="bar-{{ $device->id }}"
                                     style="width: {{ $percent }}%; background: {{ $barColor }}"></div>
                            </div>
                        </div>

                        @if ($device->is_cutoff)
                            <div class="pr-alert pr-alert-danger mb-3">
                                <i class="bi bi-power me-1"></i>
                                Units exhausted &mdash; relay has been cut off.
                                <a href="{{ route('units.create', $device) }}" class="fw-600 ms-1"
                                   style="color: var(--pr-danger)">Top up now →</a>
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="d-flex flex-wrap gap-2 mt-auto pt-2">
                            <a href="{{ route('devices.show', $device) }}" class="btn btn-pr-outline btn-sm">
                                <i class="bi bi-bar-chart me-1"></i>Details
                            </a>
                            <a href="{{ route('units.create', $device) }}" class="btn btn-pr-primary btn-sm">
                                <i class="bi bi-battery-charging me-1"></i>Top Up
                            </a>
                            <form action="{{ route('devices.relay', $device) }}" method="POST" class="m-0">
                                @csrf
                                <input type="hidden" name="on" value="{{ $device->relay_state ? '0' : '1' }}">
                                <button class="btn btn-sm {{ $device->relay_state ? 'btn-pr-danger' : 'btn-pr-outline' }}"
                                        {{ $device->is_cutoff && !$device->relay_state ? 'disabled title=Top up to restore' : '' }}>
                                    <i class="bi bi-toggle-{{ $device->relay_state ? 'on' : 'off' }} me-1"></i>
                                    {{ $device->relay_state ? 'Turn Off' : 'Turn On' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Recent activity --}}
        <div class="row g-4 mt-2">
            {{-- Recent readings --}}
            <div class="col-12 col-lg-6">
                <div class="pr-card">
                    <div class="pr-card-header">
                        <div class="fw-700"><i class="bi bi-activity me-2" style="color:var(--pr-accent)"></i>Recent Readings</div>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="pr-table">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Power</th>
                                    <th>Energy</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentReadings as $r)
                                    <tr>
                                        <td>{{ $r->device->name }}</td>
                                        <td>{{ number_format((float)$r->power_w, 1) }} W</td>
                                        <td>{{ number_format((float)$r->energy_kwh, 3) }} kWh</td>
                                        <td style="color:var(--pr-text-muted)">{{ $r->recorded_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center" style="color:var(--pr-text-muted)">No readings yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Recent transactions --}}
            <div class="col-12 col-lg-6">
                <div class="pr-card">
                    <div class="pr-card-header">
                        <div class="fw-700"><i class="bi bi-clock-history me-2" style="color:var(--pr-primary)"></i>Recent Transactions</div>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="pr-table">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentTransactions as $tx)
                                    <tr>
                                        <td>{{ $tx->device->name }}</td>
                                        <td>
                                            @if ($tx->type === 'purchase')
                                                <span class="status-pill status-on">Purchase</span>
                                            @elseif ($tx->type === 'consumption')
                                                <span class="status-pill status-warn">Used</span>
                                            @else
                                                <span class="status-pill status-off">Adj</span>
                                            @endif
                                        </td>
                                        <td class="{{ $tx->amount_kwh >= 0 ? 'glow-green' : 'glow-red' }}">
                                            {{ $tx->amount_kwh >= 0 ? '+' : '' }}{{ number_format((float)$tx->amount_kwh, 4) }} kWh
                                        </td>
                                        <td>{{ number_format((float)$tx->balance_after_kwh, 3) }} kWh</td>
                                        <td style="color:var(--pr-text-muted)">{{ $tx->created_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center" style="color:var(--pr-text-muted)">No transactions yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
// Auto-refresh device card data every 10 seconds
const deviceIds = @json($devices->pluck('id'));
async function refreshDevices() {
    for (const id of deviceIds) {
        try {
            const res  = await fetch(`/api/devices/${id}/live`);
            if (!res.ok) continue;
            const data = await res.json();

            // Power
            const pw = document.getElementById(`pw-${id}`);
            if (pw && data.reading) {
                pw.innerHTML = `${data.reading.power_w.toFixed(1)} <span style="font-size:1rem;font-weight:500;color:var(--pr-text-muted)">W</span>`;
            }

            // Balance bar
            const bar = document.getElementById(`bar-${id}`);
            const bal = document.getElementById(`bal-${id}`);
            if (bar && data.unit) {
                const pct = data.unit.balance_percent;
                bar.style.width = pct + '%';
                bar.style.background = pct > 40 ? 'var(--pr-success)' : pct > 15 ? 'var(--pr-warning)' : 'var(--pr-danger)';
                bal.textContent = data.unit.balance_kwh.toFixed(3) + ' kWh';
            }
        } catch (e) { /* network error, ignore */ }
    }
}

if (deviceIds.length > 0) {
    setInterval(refreshDevices, 10000);
}
</script>
@endpush
