<?php

namespace App\Services;

use App\Models\Device;

class MqttServiceFactory
{
    public function __construct(
        private ShellyMqttService $shellyService,
        private TasmotaMqttService $tasmotaService,
    ) {}

    /**
     * Return the appropriate MQTT service for the given device type.
     */
    public function make(Device $device): ShellyMqttService|TasmotaMqttService
    {
        return $device->isTasmota() ? $this->tasmotaService : $this->shellyService;
    }
}
