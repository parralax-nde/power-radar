@extends('layouts.app')

@section('title', 'Top Up Units – ' . $device->name)

@section('content')
<div class="mt-4">

    <div class="mb-4">
        <a href="{{ route('devices.show', $device) }}" style="color:var(--pr-text-muted);text-decoration:none;font-size:0.875rem">
            <i class="bi bi-arrow-left me-1"></i>Back to {{ $device->name }}
        </a>
        <h1 class="h3 fw-800 mt-2 mb-1 gradient-text">Top Up Units</h1>
        <p style="color:var(--pr-text-muted);font-size:0.875rem">
            Add kWh units to <strong style="color:var(--pr-text)">{{ $device->name }}</strong>
        </p>
    </div>

    <div class="row g-4 justify-content-center">

        {{-- Top-up form --}}
        <div class="col-12 col-md-6 col-lg-5">
            <div class="pr-card">
                <div class="fw-700 mb-4 fs-5">
                    <i class="bi bi-battery-charging me-2" style="color:var(--pr-success)"></i>Purchase Units
                </div>

                @if ($errors->any())
                    <div class="pr-alert pr-alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('units.store', $device) }}" method="POST">
                    @csrf

                    {{-- Quick presets --}}
                    <div class="mb-3">
                        <label class="pr-label">Quick Select</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ([1, 5, 10, 20, 50] as $preset)
                                <button type="button" class="btn btn-pr-outline btn-sm preset-btn" data-value="{{ $preset }}">
                                    {{ $preset }} kWh
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="pr-label">Custom Amount (kWh) *</label>
                        <input type="number" name="kwh_amount" id="kwh_amount"
                               value="{{ old('kwh_amount') }}"
                               class="form-control pr-form-control"
                               step="0.001" min="0.001" max="10000"
                               placeholder="e.g. 10.000" required>
                    </div>

                    <div class="mb-4">
                        <label class="pr-label">Note (optional)</label>
                        <input type="text" name="note" value="{{ old('note') }}"
                               class="form-control pr-form-control"
                               placeholder="e.g. Monthly recharge">
                    </div>

                    {{-- Price preview (illustrative only) --}}
                    <div class="pr-card mb-4" style="background:var(--pr-surface-2);padding:1rem">
                        <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem">
                            <span style="color:var(--pr-text-muted)">Amount</span>
                            <span id="preview-kwh">—</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem">
                            <span style="color:var(--pr-text-muted)">Current Balance</span>
                            <span>{{ number_format((float)($device->powerUnit?->balance_kwh ?? 0), 4) }} kWh</span>
                        </div>
                        <hr class="pr-divider my-2">
                        <div class="d-flex justify-content-between fw-700">
                            <span>New Balance</span>
                            <span class="glow-green" id="preview-total">—</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-pr-primary w-100">
                        <i class="bi bi-check-circle me-1"></i>Confirm Top Up
                    </button>
                </form>
            </div>
        </div>

        {{-- Current status + history --}}
        <div class="col-12 col-md-6 col-lg-5">
            <div class="pr-card mb-4">
                <div class="fw-700 mb-3">
                    <i class="bi bi-battery-half me-2" style="color:var(--pr-success)"></i>Current Balance
                </div>

                @php
                    $unit    = $device->powerUnit;
                    $balance = (float)($unit?->balance_kwh ?? 0);
                    $percent = $unit?->balance_percent ?? 0;
                    $barColor= $percent > 40 ? 'var(--pr-success)' : ($percent > 15 ? 'var(--pr-warning)' : 'var(--pr-danger)');
                @endphp

                <div class="text-center mb-3">
                    <div class="fw-800 {{ $balance > 0 ? 'glow-green' : 'glow-red' }}"
                         style="font-size:2.5rem;line-height:1.2">
                        {{ number_format($balance, 4) }}
                    </div>
                    <div style="font-size:0.9rem;color:var(--pr-text-muted)">kWh available</div>
                </div>

                <div class="pr-progress mb-2">
                    <div class="pr-progress-bar" style="width:{{ $percent }}%; background:{{ $barColor }}"></div>
                </div>

                @if ($unit?->is_cutoff)
                    <div class="pr-alert pr-alert-danger mt-3">
                        <i class="bi bi-power me-1"></i>Relay is currently <strong>cut off</strong>.
                        It will be restored automatically after top-up.
                    </div>
                @endif
            </div>

            <div class="pr-card">
                <div class="fw-700 mb-3">
                    <i class="bi bi-clock-history me-2" style="color:var(--pr-primary)"></i>Recent Transactions
                </div>
                @forelse ($device->unitTransactions as $tx)
                    <div class="d-flex align-items-center justify-content-between py-2"
                         style="border-bottom: 1px solid var(--pr-border)">
                        <div>
                            <div class="fw-500" style="font-size:0.85rem">
                                {{ $tx->type === 'purchase' ? 'Top Up' : ($tx->type === 'consumption' ? 'Consumed' : 'Adjustment') }}
                            </div>
                            <div style="font-size:0.75rem;color:var(--pr-text-muted)">{{ $tx->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="fw-700 {{ $tx->amount_kwh >= 0 ? 'glow-green' : 'glow-red' }}" style="font-size:0.9rem">
                            {{ $tx->amount_kwh >= 0 ? '+' : '' }}{{ number_format((float)$tx->amount_kwh, 4) }} kWh
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3" style="color:var(--pr-text-muted);font-size:0.875rem">
                        No transactions yet
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
const currentBalance = {{ (float)($device->powerUnit?->balance_kwh ?? 0) }};

document.querySelectorAll('.preset-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const val = btn.dataset.value;
        document.getElementById('kwh_amount').value = val;
        updatePreview(parseFloat(val));
    });
});

document.getElementById('kwh_amount').addEventListener('input', function () {
    updatePreview(parseFloat(this.value) || 0);
});

function updatePreview(amount) {
    document.getElementById('preview-kwh').textContent  = amount.toFixed(4) + ' kWh';
    document.getElementById('preview-total').textContent = (currentBalance + amount).toFixed(4) + ' kWh';
}
</script>
@endpush
