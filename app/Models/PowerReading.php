<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PowerReading extends Model
{
    protected $fillable = [
        'device_id',
        'power_w',
        'voltage_v',
        'current_a',
        'energy_kwh',
        'pf',
        'temperature_c',
        'recorded_at',
    ];

    protected $casts = [
        'power_w'      => 'decimal:2',
        'voltage_v'    => 'decimal:2',
        'current_a'    => 'decimal:4',
        'energy_kwh'   => 'decimal:6',
        'pf'           => 'decimal:4',
        'temperature_c'=> 'decimal:2',
        'recorded_at'  => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
