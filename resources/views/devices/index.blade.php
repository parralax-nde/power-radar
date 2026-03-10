@extends('layouts.app')

@section('title', 'Devices')

@section('content')
<div class="mt-4">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-800 mb-1 gradient-text">Devices</h1>
            <p class="mb-0" style="color: var(--pr-text-muted); font-size: 0.875rem;">
                Manage your Shelly 1PM Mini Gen3 devices
            </p>
        </div>
        <a href="{{ route('devices.create') }}" class="btn btn-pr-primary">
            <i class="bi bi-plus-lg me-1"></i>Add Device
        </a>
    </div>

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
        <div class="pr-card">
            <div style="overflow-x:auto">
                <table class="pr-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Shelly ID</th>
                            <th>Status</th>
                            <th>Balance</th>
                            <th>Power</th>
                            <th>Last Seen</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $device)
                            <tr>
                                <td class="fw-600">{{ $device->name }}</td>
                                <td style="color:var(--pr-text-muted);font-size:0.8rem">{{ $device->shelly_id }}</td>
                                <td>
                                    @if ($device->is_cutoff)
                                        <span class="status-pill status-off"><span class="dot"></span>Cut Off</span>
                                    @elseif ($device->relay_state)
                                        <span class="status-pill status-on"><span class="dot"></span>ON</span>
                                    @else
                                        <span class="status-pill status-off"><span class="dot"></span>OFF</span>
                                    @endif
                                </td>
                                <td>{{ number_format((float)($device->powerUnit?->balance_kwh ?? 0), 3) }} kWh</td>
                                <td>{{ $device->latestReading ? number_format((float)$device->latestReading->power_w, 1).' W' : '—' }}</td>
                                <td style="color:var(--pr-text-muted);font-size:0.8rem">
                                    {{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('devices.show', $device) }}" class="btn btn-pr-outline btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('units.create', $device) }}" class="btn btn-pr-primary btn-sm">
                                            <i class="bi bi-battery-charging"></i>
                                        </a>
                                        <a href="{{ route('devices.edit', $device) }}" class="btn btn-pr-outline btn-sm">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('devices.destroy', $device) }}" method="POST"
                                              onsubmit="return confirm('Delete this device?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-pr-danger btn-sm">
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
        </div>
    @endif

</div>
@endsection
