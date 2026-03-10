@extends('layouts.app')

@section('title', 'Add Device')

@section('content')
<div class="mt-4">

    <div class="mb-4">
        <a href="{{ route('devices.index') }}" style="color:var(--pr-text-muted);text-decoration:none;font-size:0.875rem">
            <i class="bi bi-arrow-left me-1"></i>Back to Devices
        </a>
        <h1 class="h3 fw-800 mt-2 mb-1 gradient-text">Add New Device</h1>
        <p style="color:var(--pr-text-muted);font-size:0.875rem">Connect a power monitoring device via MQTT (Shelly Gen3 or Tasmota / Athom)</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-7 col-xl-6">
            <div class="pr-card">
                @if ($errors->any())
                    <div class="pr-alert pr-alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('devices.store') }}" method="POST">
                    @csrf

                    {{-- Device type --}}
                    <h5 class="fw-700 mb-3" style="color:var(--pr-accent)">
                        <i class="bi bi-diagram-3 me-1"></i>Device Type
                    </h5>
                    <div class="row g-3 mb-2">
                        <div class="col-6">
                            <label class="device-type-card selected" id="card-shelly">
                                <input type="radio" name="device_type" value="shelly"
                                       {{ old('device_type', 'shelly') === 'shelly' ? 'checked' : '' }}
                                       class="d-none" onchange="switchDeviceType('shelly')">
                                <div class="text-center py-1">
                                    <i class="bi bi-broadcast" style="color:var(--pr-accent);font-size:1.3rem"></i>
                                    <div class="fw-700 mt-1" style="color:var(--pr-accent);font-size:0.9rem">Shelly Gen3</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="device-type-card" id="card-tasmota">
                                <input type="radio" name="device_type" value="tasmota"
                                       {{ old('device_type') === 'tasmota' ? 'checked' : '' }}
                                       class="d-none" onchange="switchDeviceType('tasmota')">
                                <div class="text-center py-1">
                                    <i class="bi bi-cpu-fill" style="color:var(--pr-success);font-size:1.3rem"></i>
                                    <div class="fw-700 mt-1" style="color:var(--pr-success);font-size:0.9rem">Tasmota / Athom</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div id="topic-info-shelly" class="mb-4 px-2" style="font-size:0.78rem;color:var(--pr-text-muted)">
                        Topics: <code style="color:var(--pr-text)">{prefix}/{device-id}/status/switch:0</code>
                    </div>
                    <div id="topic-info-tasmota" style="display:none" class="mb-4 px-2" style="font-size:0.78rem;color:var(--pr-text-muted)">
                        Topics: <code style="color:var(--pr-text)">tele/{device-id}/SENSOR</code> &amp; <code style="color:var(--pr-text)">cmnd/{device-id}/Power</code>
                    </div>

                    <hr class="pr-divider mb-4">

                    <h5 class="fw-700 mb-3" style="color:var(--pr-accent)">
                        <i class="bi bi-info-circle me-1"></i>Device Info
                    </h5>

                    <div class="mb-3">
                        <label class="pr-label">Device Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control pr-form-control" placeholder="e.g. Living Room Socket" required>
                    </div>

                    <div class="mb-3">
                        <label class="pr-label" id="device-id-label">Shelly Device ID *</label>
                        <input type="text" name="shelly_id" value="{{ old('shelly_id') }}"
                               class="form-control pr-form-control" id="device-id-input"
                               placeholder="e.g. shellypmmini3-AABBCCDDEEFF" required>
                        <div id="device-id-hint" style="font-size:0.75rem;color:var(--pr-text-muted);margin-top:0.3rem">
                            Found on the Shelly app or device label.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="pr-label">Device IP Address</label>
                        <input type="text" name="ip_address" value="{{ old('ip_address') }}"
                               class="form-control pr-form-control" placeholder="192.168.1.50">
                    </div>

                    <hr class="pr-divider mb-4">

                    <h5 class="fw-700 mb-3" style="color:var(--pr-accent)">
                        <i class="bi bi-wifi me-1"></i>MQTT Settings
                    </h5>

                    <div class="row g-3 mb-3">
                        <div class="col-8">
                            <label class="pr-label">MQTT Broker Host *</label>
                            <input type="text" name="mqtt_host" value="{{ old('mqtt_host', 'localhost') }}"
                                   class="form-control pr-form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="pr-label">Port *</label>
                            <input type="number" name="mqtt_port" value="{{ old('mqtt_port', 1883) }}"
                                   class="form-control pr-form-control" min="1" max="65535" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="pr-label">MQTT Username</label>
                            <input type="text" name="mqtt_username" value="{{ old('mqtt_username') }}"
                                   class="form-control pr-form-control" placeholder="Optional">
                        </div>
                        <div class="col-6">
                            <label class="pr-label">MQTT Password</label>
                            <input type="password" name="mqtt_password"
                                   class="form-control pr-form-control" placeholder="Optional">
                        </div>
                    </div>

                    <div class="mb-4" id="prefix-row">
                        <label class="pr-label">MQTT Topic Prefix (Shelly)</label>
                        <input type="text" name="mqtt_prefix" value="{{ old('mqtt_prefix', 'shellyplus1pm') }}"
                               class="form-control pr-form-control">
                        <div style="font-size:0.75rem;color:var(--pr-text-muted);margin-top:0.3rem">
                            For Gen3 Mini: <code style="color:var(--pr-accent)">shellyplus1pm</code> or
                            <code style="color:var(--pr-accent)">shellypmmini3</code>
                        </div>
                    </div>

                    <hr class="pr-divider mb-4">

                    <h5 class="fw-700 mb-3" style="color:var(--pr-accent)">
                        <i class="bi bi-battery me-1"></i>Units & Cut-off
                    </h5>

                    <div class="mb-3">
                        <label class="pr-label">Auto Cut-off Threshold (kWh)</label>
                        <input type="number" name="cutoff_units" value="{{ old('cutoff_units', 0) }}"
                               class="form-control pr-form-control" step="0.001" min="0">
                        <div style="font-size:0.75rem;color:var(--pr-text-muted);margin-top:0.3rem">
                            Relay will be switched OFF when balance reaches this value (0 = cut off at empty).
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch" style="padding-left:2.5rem">
                            <input class="form-check-input" type="checkbox" id="auto_cutoff_enabled"
                                   name="auto_cutoff_enabled" value="1"
                                   {{ old('auto_cutoff_enabled', '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-500" for="auto_cutoff_enabled"
                                   style="color:var(--pr-text)">
                                Enable auto cut-off when units run out
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-pr-primary">
                            <i class="bi bi-check-lg me-1"></i>Add Device
                        </button>
                        <a href="{{ route('devices.index') }}" class="btn btn-pr-outline">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function switchDeviceType(type) {
    const shellyCard    = document.getElementById('card-shelly');
    const tasmotaCard   = document.getElementById('card-tasmota');
    const shellyInfo    = document.getElementById('topic-info-shelly');
    const tasmotaInfo   = document.getElementById('topic-info-tasmota');
    const prefixRow     = document.getElementById('prefix-row');
    const deviceIdLabel = document.getElementById('device-id-label');
    const deviceIdHint  = document.getElementById('device-id-hint');
    const deviceIdInput = document.getElementById('device-id-input');

    if (type === 'tasmota') {
        tasmotaCard.classList.add('selected');
        shellyCard.classList.remove('selected');
        shellyInfo.style.display = 'none';
        tasmotaInfo.style.display = '';
        prefixRow.style.display = 'none';
        deviceIdLabel.textContent = 'Tasmota Topic Name *';
        deviceIdHint.textContent  = 'The MQTT "Topic" from Tasmota → Configuration → MQTT';
        deviceIdInput.placeholder = 'e.g. tasmota-switch or power-meter-1';
    } else {
        shellyCard.classList.add('selected');
        tasmotaCard.classList.remove('selected');
        tasmotaInfo.style.display = 'none';
        shellyInfo.style.display = '';
        prefixRow.style.display = '';
        deviceIdLabel.textContent = 'Shelly Device ID *';
        deviceIdHint.textContent  = 'Found on the Shelly app or device label.';
        deviceIdInput.placeholder = 'e.g. shellypmmini3-AABBCCDDEEFF';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    const selected = document.querySelector('input[name="device_type"]:checked')?.value || 'shelly';
    switchDeviceType(selected);
});
</script>
<style>
.device-type-card {
    display: block;
    cursor: pointer;
    border: 2px solid var(--pr-border);
    border-radius: 10px;
    padding: 0.75rem;
    transition: all 0.2s;
    background: var(--pr-surface-2);
}
.device-type-card:hover { border-color: rgba(108,99,255,0.4); }
.device-type-card.selected {
    border-color: var(--pr-primary);
    background: rgba(108,99,255,0.1);
}
</style>
@endpush
