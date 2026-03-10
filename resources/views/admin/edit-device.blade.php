@extends('layouts.app')

@section('title', 'Edit Device – Admin')

@section('content')
<div class="mt-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-pr-outline btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="h3 fw-800 mb-1 gradient-text">
                <i class="bi bi-pencil me-2"></i>Edit Device
            </h1>
            <p class="mb-0" style="color: var(--pr-text-muted); font-size: 0.875rem;">
                {{ $device->name }} &mdash; {{ $device->deviceTypeLabel() }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.devices.update', $device) }}">
        @csrf @method('PUT')

        <div class="row g-4">
            <div class="col-12 col-lg-8">

                {{-- Device type --}}
                <div class="pr-card mb-4">
                    <h5 class="fw-700 mb-3"><i class="bi bi-diagram-3 me-2"></i>Device Type</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="device-type-card {{ $device->device_type === 'shelly' ? 'selected' : '' }}" id="card-shelly">
                                <input type="radio" name="device_type" value="shelly"
                                       {{ $device->device_type === 'shelly' ? 'checked' : '' }}
                                       class="d-none" onchange="switchDeviceType('shelly')">
                                <div class="text-center py-2">
                                    <div class="icon-box icon-box-blue mx-auto mb-2" style="width:40px;height:40px;font-size:1.1rem">
                                        <i class="bi bi-broadcast"></i>
                                    </div>
                                    <div class="fw-700" style="color:var(--pr-accent)">Shelly Gen3</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="device-type-card {{ $device->device_type === 'tasmota' ? 'selected' : '' }}" id="card-tasmota">
                                <input type="radio" name="device_type" value="tasmota"
                                       {{ $device->device_type === 'tasmota' ? 'checked' : '' }}
                                       class="d-none" onchange="switchDeviceType('tasmota')">
                                <div class="text-center py-2">
                                    <div class="icon-box icon-box-green mx-auto mb-2" style="width:40px;height:40px;font-size:1.1rem">
                                        <i class="bi bi-cpu-fill"></i>
                                    </div>
                                    <div class="fw-700" style="color:var(--pr-success)">Tasmota / Athom</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Device info --}}
                <div class="pr-card mb-4">
                    <h5 class="fw-700 mb-3"><i class="bi bi-info-circle me-2"></i>Device Info</h5>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="color:var(--pr-text)">Display Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control pr-form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $device->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label fw-600" style="color:var(--pr-text)">
                                <span id="device-id-label">{{ $device->isTasmota() ? 'Tasmota Topic Name' : 'Shelly Device ID' }}</span>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="shelly_id" class="form-control pr-form-control @error('shelly_id') is-invalid @enderror"
                                   value="{{ old('shelly_id', $device->shelly_id) }}" required>
                            @error('shelly_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Local IP</label>
                            <input type="text" name="ip_address" class="form-control pr-form-control"
                                   value="{{ old('ip_address', $device->ip_address) }}">
                        </div>
                    </div>
                </div>

                {{-- MQTT --}}
                <div class="pr-card mb-4">
                    <h5 class="fw-700 mb-3"><i class="bi bi-hdd-network me-2"></i>MQTT Broker</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Host <span class="text-danger">*</span></label>
                            <input type="text" name="mqtt_host" class="form-control pr-form-control @error('mqtt_host') is-invalid @enderror"
                                   value="{{ old('mqtt_host', $device->mqtt_host) }}" required>
                            @error('mqtt_host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Port <span class="text-danger">*</span></label>
                            <input type="number" name="mqtt_port" class="form-control pr-form-control @error('mqtt_port') is-invalid @enderror"
                                   value="{{ old('mqtt_port', $device->mqtt_port) }}" min="1" max="65535" required>
                            @error('mqtt_port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Username</label>
                            <input type="text" name="mqtt_username" class="form-control pr-form-control"
                                   value="{{ old('mqtt_username', $device->mqtt_username) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Password</label>
                            <input type="password" name="mqtt_password" class="form-control pr-form-control" placeholder="(leave blank to keep)">
                        </div>
                    </div>
                    <div id="prefix-row" {{ $device->isTasmota() ? 'class=d-none' : '' }}>
                        <label class="form-label fw-600" style="color:var(--pr-text)">MQTT Prefix (Shelly)</label>
                        <input type="text" name="mqtt_prefix" class="form-control pr-form-control"
                               value="{{ old('mqtt_prefix', $device->mqtt_prefix) }}" id="mqtt-prefix-input">
                    </div>
                </div>

            </div>

            <div class="col-12 col-lg-4">
                <div class="pr-card mb-4">
                    <h5 class="fw-700 mb-3"><i class="bi bi-sliders me-2"></i>Settings</h5>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="color:var(--pr-text)">Cut-off Threshold (kWh)</label>
                        <input type="number" name="cutoff_units" step="0.0001" min="0"
                               class="form-control pr-form-control @error('cutoff_units') is-invalid @enderror"
                               value="{{ old('cutoff_units', $device->cutoff_units) }}">
                        @error('cutoff_units') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-check form-switch mb-3" style="padding-left:2.5rem">
                        <input class="form-check-input" type="checkbox" name="auto_cutoff_enabled"
                               id="auto_cutoff_enabled" value="1"
                               {{ old('auto_cutoff_enabled', $device->auto_cutoff_enabled) ? 'checked' : '' }}>
                        <label class="form-check-label fw-600" for="auto_cutoff_enabled" style="color:var(--pr-text)">
                            Auto Cut-off
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3" style="padding-left:2.5rem">
                        <input class="form-check-input" type="checkbox" name="active"
                               id="active" value="1"
                               {{ old('active', $device->active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-600" for="active" style="color:var(--pr-text)">
                            Active
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-pr-primary w-100 mb-2">
                    <i class="bi bi-check-circle me-2"></i>Save Changes
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-pr-outline w-100">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function switchDeviceType(type) {
    const shellyCard    = document.getElementById('card-shelly');
    const tasmotaCard   = document.getElementById('card-tasmota');
    const prefixRow     = document.getElementById('prefix-row');
    const deviceIdLabel = document.getElementById('device-id-label');

    if (type === 'tasmota') {
        tasmotaCard.classList.add('selected');
        shellyCard.classList.remove('selected');
        prefixRow.classList.add('d-none');
        deviceIdLabel.textContent = 'Tasmota Topic Name';
    } else {
        shellyCard.classList.add('selected');
        tasmotaCard.classList.remove('selected');
        prefixRow.classList.remove('d-none');
        deviceIdLabel.textContent = 'Shelly Device ID';
    }
}
</script>
<style>
.device-type-card {
    display: block;
    cursor: pointer;
    border: 2px solid var(--pr-border);
    border-radius: 12px;
    padding: 0.75rem;
    transition: all 0.2s;
    background: var(--pr-surface-2);
}
.device-type-card:hover { border-color: rgba(108,99,255,0.4); }
.device-type-card.selected {
    border-color: var(--pr-primary);
    background: rgba(108,99,255,0.1);
    box-shadow: 0 0 0 3px rgba(108,99,255,0.15);
}
</style>
@endpush
