<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PowerUnit extends Model
{
    protected $fillable = [
        'device_id',
        'balance_kwh',
        'total_purchased_kwh',
        'total_consumed_kwh',
        'energy_kwh_at_last_reset',
        'is_cutoff',
        'cutoff_at',
    ];

    protected $casts = [
        'balance_kwh'              => 'decimal:6',
        'total_purchased_kwh'      => 'decimal:6',
        'total_consumed_kwh'       => 'decimal:6',
        'energy_kwh_at_last_reset' => 'decimal:6',
        'is_cutoff'                => 'boolean',
        'cutoff_at'                => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /** Balance as a percentage of last top-up */
    public function getBalancePercentAttribute(): float
    {
        if ($this->total_purchased_kwh <= 0) {
            return 0;
        }
        return min(100, round(($this->balance_kwh / $this->total_purchased_kwh) * 100, 1));
    }
}
