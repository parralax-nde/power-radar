@extends('layouts.app')

@section('title', 'Onboard New System')

@section('content')
<div class="mt-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-pr-outline btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="h3 fw-800 mb-1 gradient-text">
                <i class="bi bi-plus-circle me-2"></i>Onboard New System
            </h1>
            <p class="mb-0" style="color: var(--pr-text-muted); font-size: 0.875rem;">
                Register a new power monitoring device – supports Shelly Gen3 and Tasmota / Athom MQTT formats
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.onboard.store') }}" id="onboardForm">
        @csrf

        <div class="row g-4">

            {{-- Left column: core settings --}}
            <div class="col-12 col-lg-7">

                {{-- Device type selector --}}
                <div class="pr-card mb-4">
                    <h5 class="fw-700 mb-3"><i class="bi bi-diagram-3 me-2"></i>Device Type</h5>
                    <p style="color:var(--pr-text-muted);font-size:0.875rem;margin-bottom:1rem">
                        Choose the MQTT protocol your device uses.
                    </p>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="device-type-card" id="card-shelly">
                                <input type="radio" name="device_type" value="shelly" checked class="d-none" onchange="switchDeviceType('shelly')">
                                <div class="text-center py-2">
                                    <div class="icon-box icon-box-blue mx-auto mb-2" style="width:48px;height:48px;font-size:1.3rem">
                                        <i class="bi bi-broadcast"></i>
                                    </div>
                                    <div class="fw-700" style="color:var(--pr-accent)">Shelly Gen3</div>
                                    <div style="font-size:0.78rem;color:var(--pr-text-muted);margin-top:0.25rem">
                                        Shelly 1PM Mini Gen3<br>RPC over MQTT
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="device-type-card" id="card-tasmota">
                                <input type="radio" name="device_type" value="tasmota" class="d-none" onchange="switchDeviceType('tasmota')">
                                <div class="text-center py-2">
                                    <div class="icon-box icon-box-green mx-auto mb-2" style="width:48px;height:48px;font-size:1.3rem">
                                        <i class="bi bi-cpu-fill"></i>
                                    </div>
                                    <div class="fw-700" style="color:var(--pr-success)">Tasmota / Athom</div>
                                    <div style="font-size:0.78rem;color:var(--pr-text-muted);margin-top:0.25rem">
                                        Athom PM, Tasmota devices<br>tele/cmnd/stat topics
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- MQTT topic reference --}}
                    <div id="topic-info-shelly" class="topic-info mt-3 p-3" style="background:rgba(0,212,255,0.05);border:1px solid rgba(0,212,255,0.15);border-radius:8px">
                        <div class="fw-600 mb-2" style="color:var(--pr-accent);font-size:0.85rem">
                            <i class="bi bi-info-circle me-1"></i>Shelly MQTT Topics
                        </div>
                        <div style="font-size:0.8rem;color:var(--pr-text-muted)">
                            Status: <code style="color:var(--pr-text)">{prefix}/{device-id}/status/switch:0</code><br>
                            Command: <code style="color:var(--pr-text)">{prefix}/{device-id}/rpc</code>
                        </div>
                    </div>
                    <div id="topic-info-tasmota" class="topic-info mt-3 p-3 d-none" style="background:rgba(0,200,150,0.05);border:1px solid rgba(0,200,150,0.15);border-radius:8px">
                        <div class="fw-600 mb-2" style="color:var(--pr-success);font-size:0.85rem">
                            <i class="bi bi-info-circle me-1"></i>Tasmota / Athom MQTT Topics
                        </div>
                        <div style="font-size:0.8rem;color:var(--pr-text-muted)">
                            Status: <code style="color:var(--pr-text)">tele/{device-id}/SENSOR</code><br>
                            Command: <code style="color:var(--pr-text)">cmnd/{device-id}/Power</code><br>
                            Result: <code style="color:var(--pr-text)">stat/{device-id}/RESULT</code>
                        </div>
                    </div>
                </div>

                {{-- Basic info --}}
                <div class="pr-card mb-4">
                    <h5 class="fw-700 mb-3"><i class="bi bi-info-circle me-2"></i>Device Info</h5>

                    <div class="mb-3">
                        <label class="form-label fw-600" style="color:var(--pr-text)">Display Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control pr-form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. Kitchen Switch, Room 3 Meter" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-8">
                            <label class="form-label fw-600" style="color:var(--pr-text)">
                                <span id="device-id-label">Shelly Device ID</span>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="shelly_id" class="form-control pr-form-control @error('shelly_id') is-invalid @enderror"
                                   value="{{ old('shelly_id') }}" id="device-id-input"
                                   placeholder="shellypmmini3-AABBCCDDEEFF" required>
                            <div class="form-text" id="device-id-hint" style="color:var(--pr-text-muted);font-size:0.78rem">
                                Found on the device label or Shelly app
                            </div>
                            @error('shelly_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Local IP (optional)</label>
                            <input type="text" name="ip_address" class="form-control pr-form-control @error('ip_address') is-invalid @enderror"
                                   value="{{ old('ip_address') }}" placeholder="192.168.1.x">
                            @error('ip_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- MQTT config --}}
                <div class="pr-card mb-4">
                    <h5 class="fw-700 mb-3"><i class="bi bi-hdd-network me-2"></i>MQTT Broker</h5>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-8">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Broker Host <span class="text-danger">*</span></label>
                            <input type="text" name="mqtt_host" class="form-control pr-form-control @error('mqtt_host') is-invalid @enderror"
                                   value="{{ old('mqtt_host', 'localhost') }}" required>
                            @error('mqtt_host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Port <span class="text-danger">*</span></label>
                            <input type="number" name="mqtt_port" class="form-control pr-form-control @error('mqtt_port') is-invalid @enderror"
                                   value="{{ old('mqtt_port', 1883) }}" min="1" max="65535" required>
                            @error('mqtt_port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Username (optional)</label>
                            <input type="text" name="mqtt_username" class="form-control pr-form-control"
                                   value="{{ old('mqtt_username') }}" autocomplete="off">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-600" style="color:var(--pr-text)">Password (optional)</label>
                            <input type="password" name="mqtt_password" class="form-control pr-form-control" autocomplete="off">
                        </div>
                    </div>

                    {{-- Shelly-only: MQTT prefix --}}
                    <div id="prefix-row">
                        <label class="form-label fw-600" style="color:var(--pr-text)">MQTT Prefix (Shelly)</label>
                        <input type="text" name="mqtt_prefix" class="form-control pr-form-control @error('mqtt_prefix') is-invalid @enderror"
                               value="{{ old('mqtt_prefix', 'shellypmmini3') }}" id="mqtt-prefix-input"
                               placeholder="e.g. shellypmmini3">
                        <div class="form-text" style="color:var(--pr-text-muted);font-size:0.78rem">
                            The MQTT topic prefix set in the Shelly device (default: shellypmmini3)
                        </div>
                        @error('mqtt_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

            </div>

            {{-- Right column: unit settings --}}
            <div class="col-12 col-lg-5">

                <div class="pr-card mb-4">
                    <h5 class="fw-700 mb-3"><i class="bi bi-battery-charging me-2"></i>Unit / Balance Settings</h5>

                    <div class="mb-3">
                        <label class="form-label fw-600" style="color:var(--pr-text)">Initial Balance (kWh)</label>
                        <input type="number" name="initial_balance_kwh" step="0.001" min="0"
                               class="form-control pr-form-control @error('initial_balance_kwh') is-invalid @enderror"
                               value="{{ old('initial_balance_kwh', 0) }}"
                               placeholder="0.000">
                        <div class="form-text" style="color:var(--pr-text-muted);font-size:0.78rem">
                            Pre-load the device with this amount on onboarding (optional)
                        </div>
                        @error('initial_balance_kwh') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-600" style="color:var(--pr-text)">Auto Cut-off Threshold (kWh)</label>
                        <input type="number" name="cutoff_units" step="0.0001" min="0"
                               class="form-control pr-form-control @error('cutoff_units') is-invalid @enderror"
                               value="{{ old('cutoff_units', 0) }}">
                        <div class="form-text" style="color:var(--pr-text-muted);font-size:0.78rem">
                            Set to 0 to use balance = 0 as the cut-off point
                        </div>
                        @error('cutoff_units') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch mb-3" style="padding-left:2.5rem">
                        <input class="form-check-input" type="checkbox" name="auto_cutoff_enabled"
                               id="auto_cutoff_enabled" value="1"
                               {{ old('auto_cutoff_enabled', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-600" for="auto_cutoff_enabled" style="color:var(--pr-text)">
                            Enable Auto Cut-off
                        </label>
                        <div class="form-text" style="color:var(--pr-text-muted);font-size:0.78rem">
                            Automatically turn off the relay when balance hits 0
                        </div>
                    </div>
                </div>

                {{-- Quick summary card --}}
                <div class="pr-card mb-4" style="background:rgba(108,99,255,0.05);border-color:rgba(108,99,255,0.2)">
                    <h6 class="fw-700 mb-3" style="color:var(--pr-primary)"><i class="bi bi-lightbulb me-2"></i>Quick Setup Guide</h6>
                    <div id="guide-shelly" style="font-size:0.82rem;color:var(--pr-text-muted)">
                        <ol class="ps-3 mb-0">
                            <li class="mb-1">Enable MQTT in the Shelly web UI</li>
                            <li class="mb-1">Set the broker to this server's IP</li>
                            <li class="mb-1">Copy the Shelly ID from the device label</li>
                            <li class="mb-1">Run <code style="color:var(--pr-text)">php artisan mqtt:subscribe</code> to listen</li>
                        </ol>
                    </div>
                    <div id="guide-tasmota" class="d-none" style="font-size:0.82rem;color:var(--pr-text-muted)">
                        <ol class="ps-3 mb-0">
                            <li class="mb-1">Flash Tasmota or use Athom pre-flashed device</li>
                            <li class="mb-1">Set <code style="color:var(--pr-text)">MqttHost</code> to this server's IP in Tasmota console</li>
                            <li class="mb-1">Note the <code style="color:var(--pr-text)">Topic</code> from Tasmota → Configuration → MQTT</li>
                            <li class="mb-1">Enable energy monitoring in Tasmota</li>
                            <li class="mb-1">Run <code style="color:var(--pr-text)">php artisan mqtt:subscribe</code> to listen</li>
                        </ol>
                    </div>
                </div>

                <button type="submit" class="btn btn-pr-primary w-100 py-3">
                    <i class="bi bi-check-circle me-2"></i>Onboard System
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-pr-outline w-100 mt-2">
                    Cancel
                </a>
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
    const shellyInfo    = document.getElementById('topic-info-shelly');
    const tasmotaInfo   = document.getElementById('topic-info-tasmota');
    const guideShelly   = document.getElementById('guide-shelly');
    const guideTasmota  = document.getElementById('guide-tasmota');
    const prefixRow     = document.getElementById('prefix-row');
    const deviceIdLabel = document.getElementById('device-id-label');
    const deviceIdHint  = document.getElementById('device-id-hint');
    const deviceIdInput = document.getElementById('device-id-input');
    const prefixInput   = document.getElementById('mqtt-prefix-input');

    if (type === 'tasmota') {
        tasmotaCard.classList.add('selected');
        shellyCard.classList.remove('selected');
        shellyInfo.classList.add('d-none');
        tasmotaInfo.classList.remove('d-none');
        guideShelly.classList.add('d-none');
        guideTasmota.classList.remove('d-none');
        prefixRow.classList.add('d-none');
        deviceIdLabel.textContent = 'Tasmota Topic Name';
        deviceIdHint.textContent  = 'The MQTT "Topic" configured in Tasmota → Configuration → MQTT';
        deviceIdInput.placeholder = 'e.g. tasmota-switch or power-meter-1';
    } else {
        shellyCard.classList.add('selected');
        tasmotaCard.classList.remove('selected');
        tasmotaInfo.classList.add('d-none');
        shellyInfo.classList.remove('d-none');
        guideTasmota.classList.add('d-none');
        guideShelly.classList.remove('d-none');
        prefixRow.classList.remove('d-none');
        deviceIdLabel.textContent = 'Shelly Device ID';
        deviceIdHint.textContent  = 'Found on the device label or Shelly app';
        deviceIdInput.placeholder = 'shellypmmini3-AABBCCDDEEFF';
    }
}

// Select Shelly card by default on load
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('card-shelly').classList.add('selected');
    // Restore selection from old() if validation failed
    const selectedType = document.querySelector('input[name="device_type"]:checked')?.value;
    if (selectedType) switchDeviceType(selectedType);
});
</script>
<style>
.device-type-card {
    display: block;
    cursor: pointer;
    border: 2px solid var(--pr-border);
    border-radius: 12px;
    padding: 1rem;
    transition: all 0.2s;
    background: var(--pr-surface-2);
}
.device-type-card:hover {
    border-color: rgba(108, 99, 255, 0.4);
    background: rgba(108, 99, 255, 0.05);
}
.device-type-card.selected {
    border-color: var(--pr-primary);
    background: rgba(108, 99, 255, 0.1);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.15);
}
</style>
@endpush
