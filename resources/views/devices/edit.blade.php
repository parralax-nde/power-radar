@extends('layouts.app')

@section('title', 'Edit Device')

@section('content')
<div class="mt-4">

    <div class="mb-4">
        <a href="{{ route('devices.show', $device) }}" style="color:var(--pr-text-muted);text-decoration:none;font-size:0.875rem">
            <i class="bi bi-arrow-left me-1"></i>Back to {{ $device->name }}
        </a>
        <h1 class="h3 fw-800 mt-2 mb-1 gradient-text">Edit Device</h1>
        <p style="color:var(--pr-text-muted);font-size:0.875rem">{{ $device->shelly_id }}</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-7 col-xl-6">
            <div class="pr-card">
                @if ($errors->any())
                    <div class="pr-alert pr-alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('devices.update', $device) }}" method="POST">
                    @csrf @method('PUT')

                    <h5 class="fw-700 mb-3" style="color:var(--pr-accent)">
                        <i class="bi bi-info-circle me-1"></i>Device Info
                    </h5>

                    <div class="mb-3">
                        <label class="pr-label">Device Name *</label>
                        <input type="text" name="name" value="{{ old('name', $device->name) }}"
                               class="form-control pr-form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="pr-label">Shelly Device ID *</label>
                        <input type="text" name="shelly_id" value="{{ old('shelly_id', $device->shelly_id) }}"
                               class="form-control pr-form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="pr-label">Device IP Address</label>
                        <input type="text" name="ip_address" value="{{ old('ip_address', $device->ip_address) }}"
                               class="form-control pr-form-control">
                    </div>

                    <hr class="pr-divider mb-4">

                    <h5 class="fw-700 mb-3" style="color:var(--pr-accent)">
                        <i class="bi bi-wifi me-1"></i>MQTT Settings
                    </h5>

                    <div class="row g-3 mb-3">
                        <div class="col-8">
                            <label class="pr-label">MQTT Broker Host *</label>
                            <input type="text" name="mqtt_host" value="{{ old('mqtt_host', $device->mqtt_host) }}"
                                   class="form-control pr-form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="pr-label">Port *</label>
                            <input type="number" name="mqtt_port" value="{{ old('mqtt_port', $device->mqtt_port) }}"
                                   class="form-control pr-form-control" min="1" max="65535" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="pr-label">MQTT Username</label>
                            <input type="text" name="mqtt_username" value="{{ old('mqtt_username', $device->mqtt_username) }}"
                                   class="form-control pr-form-control">
                        </div>
                        <div class="col-6">
                            <label class="pr-label">MQTT Password</label>
                            <input type="password" name="mqtt_password"
                                   class="form-control pr-form-control" placeholder="Leave blank to keep current">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="pr-label">MQTT Topic Prefix *</label>
                        <input type="text" name="mqtt_prefix" value="{{ old('mqtt_prefix', $device->mqtt_prefix) }}"
                               class="form-control pr-form-control" required>
                    </div>

                    <hr class="pr-divider mb-4">

                    <h5 class="fw-700 mb-3" style="color:var(--pr-accent)">
                        <i class="bi bi-battery me-1"></i>Units & Cut-off
                    </h5>

                    <div class="mb-3">
                        <label class="pr-label">Auto Cut-off Threshold (kWh)</label>
                        <input type="number" name="cutoff_units" value="{{ old('cutoff_units', $device->cutoff_units) }}"
                               class="form-control pr-form-control" step="0.001" min="0">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch" style="padding-left:2.5rem">
                            <input class="form-check-input" type="checkbox" id="auto_cutoff_enabled"
                                   name="auto_cutoff_enabled" value="1"
                                   {{ $device->auto_cutoff_enabled ? 'checked' : '' }}>
                            <label class="form-check-label fw-500" for="auto_cutoff_enabled"
                                   style="color:var(--pr-text)">Enable auto cut-off</label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch" style="padding-left:2.5rem">
                            <input class="form-check-input" type="checkbox" id="active"
                                   name="active" value="1"
                                   {{ $device->active ? 'checked' : '' }}>
                            <label class="form-check-label fw-500" for="active"
                                   style="color:var(--pr-text)">Device active</label>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-pr-primary">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                        <a href="{{ route('devices.show', $device) }}" class="btn btn-pr-outline">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>
@endsection
