<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\MqttServiceFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MqttPollDevices extends Command
{
    protected $signature   = 'mqtt:poll {--interval=30 : Seconds between polls}';
    protected $description = 'Poll all active devices via MQTT and store power readings';

    public function handle(MqttServiceFactory $mqttFactory): int
    {
        $interval = (int) $this->option('interval');
        $this->info("Starting MQTT poll loop (interval: {$interval}s) …");

        while (true) {
            $devices = Device::where('active', true)->get();

            foreach ($devices as $device) {
                $this->line("Polling device: {$device->name} ({$device->shelly_id}) [{$device->deviceTypeLabel()}]");
                try {
                    $result = $mqttFactory->make($device)->pollStatus($device);
                    if ($result) {
                        $this->info("  Power: {$result['power_w']} W | Relay: " . ($result['relay_on'] ? 'ON' : 'OFF'));
                    } else {
                        $this->warn("  No response from device.");
                    }
                } catch (\Throwable $e) {
                    Log::error("MQTT poll failed for {$device->shelly_id}: " . $e->getMessage());
                    $this->error("  Error: " . $e->getMessage());
                }
            }

            $this->line('Next poll in ' . $interval . 's …');
            sleep($interval);
        }

        return self::SUCCESS;
    }
}
