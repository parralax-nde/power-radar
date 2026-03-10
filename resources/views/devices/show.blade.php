@extends('layouts.app')

@section('title', $device->name)

@section('content')
<div class="mt-4" id="device-show" data-device-id="{{ $device->id }}">

    {{-- Breadcrumb / header --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <a href="{{ route('devices.index') }}" style="color:var(--pr-text-muted);text-decoration:none;font-size:0.875rem">
                <i class="bi bi-arrow-left me-1"></i>Devices
            </a>
            <h1 class="h3 fw-800 mt-2 mb-1 gradient-text">{{ $device->name }}</h1>
            <div style="color:var(--pr-text-muted);font-size:0.875rem">
                {{ $device->shelly_id }} &bull; {{ $device->mqtt_host }}:{{ $device->mqtt_port }}
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            {{-- Manual poll --}}
            <form action="{{ route('devices.poll', $device) }}" method="POST" class="m-0">
                @csrf
                <button class="btn btn-pr-outline btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i>Poll Now
                </button>
            </form>

            {{-- Relay toggle --}}
            <form action="{{ route('devices.relay', $device) }}" method="POST" class="m-0">
                @csrf
                <input type="hidden" name="on" value="{{ $device->relay_state ? '0' : '1' }}">
                <button class="btn btn-sm {{ $device->relay_state ? 'btn-pr-danger' : 'btn-pr-primary' }}"
                        {{ ($device->is_cutoff && !$device->relay_state) ? 'disabled title=Top up to restore' : '' }}>
                    <i class="bi bi-toggle-{{ $device->relay_state ? 'on' : 'off' }} me-1"></i>
                    {{ $device->relay_state ? 'Turn Off' : 'Turn On' }}
                </button>
            </form>

            <a href="{{ route('units.create', $device) }}" class="btn btn-pr-primary btn-sm">
                <i class="bi bi-battery-charging me-1"></i>Top Up
            </a>
            <a href="{{ route('devices.edit', $device) }}" class="btn btn-pr-outline btn-sm">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
        </div>
    </div>

    {{-- Cut-off alert --}}
    @if ($device->is_cutoff)
        <div class="pr-alert pr-alert-danger mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span><i class="bi bi-power me-2"></i><strong>Units Exhausted:</strong> The relay has been automatically cut off.</span>
            <a href="{{ route('units.create', $device) }}" class="btn btn-pr-danger btn-sm">
                <i class="bi bi-battery-charging me-1"></i>Top Up Now
            </a>
        </div>
    @endif

    {{-- Live metrics --}}
    <div class="row g-3 mb-4">
        @php
            $reading = $device->latestReading;
            $unit    = $device->powerUnit;
            $balance = (float)($unit?->balance_kwh ?? 0);
            $percent = $unit?->balance_percent ?? 0;
            $barColor= $percent > 40 ? 'var(--pr-success)' : ($percent > 15 ? 'var(--pr-warning)' : 'var(--pr-danger)');
        @endphp

        <div class="col-6 col-md-4 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-blue mx-auto mb-2"><i class="bi bi-lightning-charge"></i></div>
                <div class="metric-value glow-blue" id="live-power">
                    {{ $reading ? number_format((float)$reading->power_w,1) : '—' }}
                </div>
                <div class="metric-label">Watts</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-purple mx-auto mb-2"><i class="bi bi-plug"></i></div>
                <div class="metric-value glow-purple" id="live-voltage">
                    {{ $reading?->voltage_v ? number_format((float)$reading->voltage_v,0) : '—' }}
                </div>
                <div class="metric-label">Volts</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-yellow mx-auto mb-2"><i class="bi bi-tsunami"></i></div>
                <div class="metric-value glow-yellow" id="live-current">
                    {{ $reading?->current_a ? number_format((float)$reading->current_a,2) : '—' }}
                </div>
                <div class="metric-label">Amps</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-green mx-auto mb-2"><i class="bi bi-thermometer-half"></i></div>
                <div class="metric-value glow-green" id="live-temp">
                    {{ $reading?->temperature_c ? number_format((float)$reading->temperature_c,1) : '—' }}
                </div>
                <div class="metric-label">°C</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-blue mx-auto mb-2"><i class="bi bi-bar-chart-steps"></i></div>
                <div class="metric-value glow-blue" id="live-energy">
                    {{ $reading ? number_format((float)$reading->energy_kwh,3) : '—' }}
                </div>
                <div class="metric-label">kWh Total</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="metric-card">
                <div class="icon-box {{ $device->relay_state ? 'icon-box-green' : 'icon-box-red' }} mx-auto mb-2">
                    <i class="bi bi-toggle-{{ $device->relay_state ? 'on' : 'off' }}"></i>
                </div>
                <div class="metric-value {{ $device->relay_state ? 'glow-green' : 'glow-red' }}">
                    {{ $device->relay_state ? 'ON' : 'OFF' }}
                </div>
                <div class="metric-label">Relay</div>
            </div>
        </div>
    </div>

    {{-- Balance + Chart --}}
    <div class="row g-4 mb-4">
        {{-- Balance card --}}
        <div class="col-12 col-lg-4">
            <div class="pr-card h-100">
                <div class="fw-700 mb-3">
                    <i class="bi bi-battery-half me-2" style="color:var(--pr-success)"></i>Units Balance
                </div>

                <div class="text-center mb-3">
                    <div style="font-size:0.75rem;color:var(--pr-text-muted);text-transform:uppercase;letter-spacing:.1em">Available</div>
                    <div class="fw-800 glow-green" style="font-size:2.5rem;line-height:1.2" id="bal-kwh">
                        {{ number_format($balance, 4) }}
                    </div>
                    <div style="font-size:1rem;color:var(--pr-text-muted)">kWh</div>
                </div>

                <div class="pr-progress mb-2">
                    <div class="pr-progress-bar" id="bal-bar"
                         style="width: {{ $percent }}%; background: {{ $barColor }}"></div>
                </div>
                <div class="d-flex justify-content-between" style="font-size:0.75rem;color:var(--pr-text-muted)">
                    <span>0</span>
                    <span id="bal-pct">{{ $percent }}%</span>
                    <span>{{ number_format((float)($unit?->total_purchased_kwh ?? 0), 3) }} kWh total</span>
                </div>

                <hr class="pr-divider my-3">

                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div style="font-size:0.7rem;color:var(--pr-text-muted)">Total Purchased</div>
                        <div class="fw-600">{{ number_format((float)($unit?->total_purchased_kwh ?? 0), 3) }} kWh</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:0.7rem;color:var(--pr-text-muted)">Total Consumed</div>
                        <div class="fw-600">{{ number_format((float)($unit?->total_consumed_kwh ?? 0), 3) }} kWh</div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="{{ route('units.create', $device) }}" class="btn btn-pr-primary w-100">
                        <i class="bi bi-battery-charging me-1"></i>Top Up Units
                    </a>
                </div>
            </div>
        </div>

        {{-- Chart --}}
        <div class="col-12 col-lg-8">
            <div class="pr-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="fw-700">
                        <i class="bi bi-graph-up me-2" style="color:var(--pr-accent)"></i>Power Usage (Last 20 readings)
                    </div>
                    <div style="font-size:0.75rem;color:var(--pr-text-muted)" id="chart-updated">
                        Auto-refreshing every 10s
                    </div>
                </div>
                <div class="chart-container" style="height:220px">
                    <canvas id="powerChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions table --}}
    <div class="pr-card">
        <div class="pr-card-header">
            <div class="fw-700">
                <i class="bi bi-clock-history me-2" style="color:var(--pr-primary)"></i>Transaction History
            </div>
            <a href="{{ route('units.create', $device) }}" class="btn btn-pr-primary btn-sm">
                <i class="bi bi-plus me-1"></i>Top Up
            </a>
        </div>
        <div style="overflow-x:auto">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Note</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
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
                                {{ $tx->amount_kwh >= 0 ? '+' : '' }}{{ number_format((float)$tx->amount_kwh, 6) }} kWh
                            </td>
                            <td class="fw-600">{{ number_format((float)$tx->balance_after_kwh, 4) }} kWh</td>
                            <td style="color:var(--pr-text-muted);font-size:0.8rem">{{ $tx->note ?? '—' }}</td>
                            <td style="color:var(--pr-text-muted);font-size:0.8rem">{{ $tx->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center" style="color:var(--pr-text-muted)">No transactions yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const deviceId = {{ $device->id }};

// Initial chart data
const initialLabels = @json($chartReadings->pluck('recorded_at')->map->format('H:i:s')->toArray());
const initialPower  = @json($chartReadings->pluck('power_w')->map(fn($v) => round((float)$v, 2))->toArray());

const ctx = document.getElementById('powerChart').getContext('2d');
const powerChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: initialLabels,
        datasets: [{
            label: 'Power (W)',
            data: initialPower,
            borderColor: '#00D4FF',
            backgroundColor: 'rgba(0, 212, 255, 0.08)',
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#00D4FF',
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1A1A2E',
                borderColor: 'rgba(255,255,255,0.08)',
                borderWidth: 1,
                titleColor: '#94A3B8',
                bodyColor: '#E2E8F0',
                callbacks: {
                    label: ctx => `${ctx.parsed.y.toFixed(2)} W`
                }
            }
        },
        scales: {
            x: {
                ticks: { color: '#94A3B8', font: { size: 10 }, maxRotation: 45 },
                grid: { color: 'rgba(255,255,255,0.04)' },
            },
            y: {
                beginAtZero: true,
                ticks: { color: '#94A3B8', font: { size: 10 }, callback: v => v + ' W' },
                grid: { color: 'rgba(255,255,255,0.06)' },
            }
        }
    }
});

// Poll for live data every 10s
async function refreshLive() {
    try {
        const res  = await fetch(`/api/devices/${deviceId}/live`);
        if (!res.ok) return;
        const data = await res.json();

        if (data.reading) {
            document.getElementById('live-power').textContent   = data.reading.power_w.toFixed(1);
            document.getElementById('live-voltage').textContent = data.reading.voltage_v ? data.reading.voltage_v.toFixed(0) : '—';
            document.getElementById('live-current').textContent = data.reading.current_a ? data.reading.current_a.toFixed(2) : '—';
            document.getElementById('live-temp').textContent    = data.reading.temperature_c ? data.reading.temperature_c.toFixed(1) : '—';
            document.getElementById('live-energy').textContent  = data.reading.energy_kwh.toFixed(3);
        }

        if (data.unit) {
            document.getElementById('bal-kwh').textContent = data.unit.balance_kwh.toFixed(4);
            const pct = data.unit.balance_percent;
            document.getElementById('bal-bar').style.width = pct + '%';
            document.getElementById('bal-bar').style.background = pct > 40 ? 'var(--pr-success)' : pct > 15 ? 'var(--pr-warning)' : 'var(--pr-danger)';
            document.getElementById('bal-pct').textContent = pct + '%';
        }

        if (data.chart && data.chart.labels.length) {
            powerChart.data.labels   = data.chart.labels;
            powerChart.data.datasets[0].data = data.chart.power;
            powerChart.update('none');
        }

        document.getElementById('chart-updated').textContent = 'Updated ' + new Date().toLocaleTimeString();
    } catch(e) { /* ignore */ }
}

setInterval(refreshLive, 10000);
</script>
@endpush
