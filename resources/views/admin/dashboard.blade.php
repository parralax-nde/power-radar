@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="mt-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-800 mb-1 gradient-text">
                <i class="bi bi-shield-check me-2"></i>Admin Dashboard
            </h1>
            <p class="mb-0" style="color: var(--pr-text-muted); font-size: 0.875rem;">
                System overview and device management
            </p>
        </div>
        <a href="{{ route('admin.onboard') }}" class="btn btn-pr-primary">
            <i class="bi bi-plus-lg me-1"></i>Onboard New System
        </a>
    </div>

    {{-- Summary metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-purple mx-auto mb-2">
                    <i class="bi bi-cpu"></i>
                </div>
                <div class="metric-value glow-purple">{{ $totalDevices }}</div>
                <div class="metric-label">Total Systems</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-green mx-auto mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="metric-value glow-green">{{ $activeDevices }}</div>
                <div class="metric-label">Active</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-red mx-auto mb-2">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="metric-value glow-red">{{ $cutoffDevices }}</div>
                <div class="metric-label">Cut Off</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-blue mx-auto mb-2">
                    <i class="bi bi-lightning-charge"></i>
                </div>
                <div class="metric-value glow-blue">{{ number_format($totalPowerW, 1) }}</div>
                <div class="metric-label">Total Watts</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-purple mx-auto mb-2">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <div class="metric-value" style="font-size:1.2rem">
                    <span style="color:var(--pr-accent)">{{ $shellyCount }}</span> /
                    <span style="color:var(--pr-success)">{{ $tasmotaCount }}</span>
                </div>
                <div class="metric-label">Shelly / Tasmota</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card">
                <div class="icon-box icon-box-green mx-auto mb-2">
                    <i class="bi bi-battery-charging"></i>
                </div>
                <div class="metric-value glow-green" style="font-size:1.1rem">{{ number_format($totalBalanceKwh, 2) }}</div>
                <div class="metric-label">Total kWh Balance</div>
            </div>
        </div>
    </div>

    {{-- Devices table --}}
    <div class="pr-card mb-4">
        <div class="pr-card-header mb-3">
            <h5 class="fw-700 mb-0"><i class="bi bi-cpu me-2"></i>Registered Systems</h5>
            <a href="{{ route('admin.onboard') }}" class="btn btn-pr-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Onboard
            </a>
        </div>

        @if ($devices->isEmpty())
            <div class="text-center py-4">
                <p style="color:var(--pr-text-muted)">No systems registered yet. <a href="{{ route('admin.onboard') }}" style="color:var(--pr-primary)">Onboard your first system</a>.</p>
            </div>
        @else
            <div style="overflow-x:auto">
                <table class="pr-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Device ID</th>
                            <th>Status</th>
                            <th>Balance</th>
                            <th>Power</th>
                            <th>MQTT Host</th>
                            <th>Last Seen</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $device)
                            <tr>
                                <td class="fw-600">{{ $device->name }}</td>
                                <td>
                                    @if ($device->device_type === 'tasmota')
                                        <span class="badge" style="background:rgba(0,200,150,0.2);color:var(--pr-success);border:1px solid rgba(0,200,150,0.3);font-size:0.75rem">
                                            <i class="bi bi-cpu-fill me-1"></i>Tasmota
                                        </span>
                                    @else
                                        <span class="badge" style="background:rgba(0,212,255,0.2);color:var(--pr-accent);border:1px solid rgba(0,212,255,0.3);font-size:0.75rem">
                                            <i class="bi bi-broadcast me-1"></i>Shelly
                                        </span>
                                    @endif
                                </td>
                                <td style="color:var(--pr-text-muted);font-size:0.8rem;font-family:monospace">{{ $device->shelly_id }}</td>
                                <td>
                                    @if (!$device->active)
                                        <span class="status-pill status-off"><span class="dot"></span>Inactive</span>
                                    @elseif ($device->is_cutoff)
                                        <span class="status-pill status-off"><span class="dot"></span>Cut Off</span>
                                    @elseif ($device->relay_state)
                                        <span class="status-pill status-on"><span class="dot"></span>ON</span>
                                    @else
                                        <span class="status-pill status-off"><span class="dot"></span>OFF</span>
                                    @endif
                                </td>
                                <td>{{ number_format((float)($device->powerUnit?->balance_kwh ?? 0), 3) }} kWh</td>
                                <td>{{ $device->latestReading ? number_format((float)$device->latestReading->power_w, 1).' W' : '—' }}</td>
                                <td style="color:var(--pr-text-muted);font-size:0.8rem">{{ $device->mqtt_host }}:{{ $device->mqtt_port }}</td>
                                <td style="color:var(--pr-text-muted);font-size:0.8rem">{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('devices.show', $device) }}" class="btn btn-pr-outline btn-sm" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.devices.edit', $device) }}" class="btn btn-pr-outline btn-sm" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="{{ route('units.create', $device) }}" class="btn btn-pr-primary btn-sm" title="Top Up">
                                            <i class="bi bi-battery-charging"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.devices.relay', $device) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="on" value="{{ $device->relay_state ? '0' : '1' }}">
                                            <button type="submit" class="btn btn-sm {{ $device->relay_state ? 'btn-pr-danger' : 'btn-pr-primary' }}" title="{{ $device->relay_state ? 'Turn OFF' : 'Turn ON' }}">
                                                <i class="bi bi-toggle-{{ $device->relay_state ? 'on' : 'off' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.devices.destroy', $device) }}"
                                              onsubmit="return confirm('Remove {{ $device->name }}? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-pr-danger btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent transactions --}}
    @if ($recentTransactions->isNotEmpty())
    <div class="pr-card">
        <h5 class="fw-700 mb-3"><i class="bi bi-clock-history me-2"></i>Recent Transactions</h5>
        <div style="overflow-x:auto">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Device</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentTransactions as $tx)
                        <tr>
                            <td style="color:var(--pr-text-muted);font-size:0.8rem">{{ $tx->created_at->diffForHumans() }}</td>
                            <td class="fw-600">{{ $tx->device?->name ?? '—' }}</td>
                            <td>
                                @if ($tx->type === 'purchase')
                                    <span style="color:var(--pr-success)"><i class="bi bi-plus-circle me-1"></i>Purchase</span>
                                @elseif ($tx->type === 'consumption')
                                    <span style="color:var(--pr-warning)"><i class="bi bi-dash-circle me-1"></i>Usage</span>
                                @else
                                    <span style="color:var(--pr-text-muted)"><i class="bi bi-arrow-left-right me-1"></i>Adjustment</span>
                                @endif
                            </td>
                            <td class="{{ $tx->amount_kwh >= 0 ? 'text-success' : 'text-warning' }} fw-600">
                                {{ $tx->amount_kwh >= 0 ? '+' : '' }}{{ number_format($tx->amount_kwh, 4) }} kWh
                            </td>
                            <td>{{ number_format($tx->balance_after_kwh, 4) }} kWh</td>
                            <td style="color:var(--pr-text-muted);font-size:0.8rem">{{ $tx->note ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
