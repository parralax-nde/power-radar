<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    protected $fillable = [
        'name',
        'shelly_id',
        'ip_address',
        'mqtt_host',
        'mqtt_port',
        'mqtt_username',
        'mqtt_password',
        'mqtt_prefix',
        'cutoff_units',
        'relay_state',
        'auto_cutoff_enabled',
        'active',
        'last_seen_at',
    ];

    protected $casts = [
        'relay_state'          => 'boolean',
        'auto_cutoff_enabled'  => 'boolean',
        'active'               => 'boolean',
        'cutoff_units'         => 'decimal:4',
        'last_seen_at'         => 'datetime',
    ];

    public function powerReadings(): HasMany
    {
        return $this->hasMany(PowerReading::class);
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(PowerReading::class)->latestOfMany('recorded_at');
    }

    public function powerUnit(): HasOne
    {
        return $this->hasOne(PowerUnit::class);
    }

    public function unitTransactions(): HasMany
    {
        return $this->hasMany(UnitTransaction::class);
    }

    /** MQTT topic for status messages from device */
    public function statusTopic(): string
    {
        return "{$this->mqtt_prefix}/{$this->shelly_id}/status/switch:0";
    }

    /** MQTT topic for RPC commands to device */
    public function rpcTopic(): string
    {
        return "{$this->mqtt_prefix}/{$this->shelly_id}/rpc";
    }

    /** MQTT topic for RPC responses from device */
    public function rpcResponseTopic(): string
    {
        return "{$this->mqtt_prefix}/{$this->shelly_id}/rpc";
    }

    /** Current balance in kWh */
    public function getBalanceKwhAttribute(): float
    {
        return (float) ($this->powerUnit?->balance_kwh ?? 0);
    }

    /** Is this device cut off due to low units? */
    public function getIsCutoffAttribute(): bool
    {
        return (bool) ($this->powerUnit?->is_cutoff ?? false);
    }
}
