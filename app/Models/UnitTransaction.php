<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitTransaction extends Model
{
    protected $fillable = [
        'device_id',
        'type',
        'amount_kwh',
        'balance_after_kwh',
        'note',
    ];

    protected $casts = [
        'amount_kwh'       => 'decimal:6',
        'balance_after_kwh'=> 'decimal:6',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
