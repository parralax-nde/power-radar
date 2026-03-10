<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\ShellyMqttService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttSubscribe extends Command
{
    protected $signature   = 'mqtt:subscribe';
    protected $description = 'Subscribe to all active Shelly device MQTT topics and process messages in real-time';

    public function handle(ShellyMqttService $shellyService): int
    {
        $devices = Device::where('active', true)->get();

        if ($devices->isEmpty()) {
            $this->warn('No active devices found. Add a device first.');
            return self::FAILURE;
        }

        // Group devices by MQTT host:port for efficiency
        $groups = $devices->groupBy(fn ($d) => "{$d->mqtt_host}:{$d->mqtt_port}");

        $this->info("Subscribing to {$devices->count()} device(s) across {$groups->count()} broker(s).");

        // For simplicity, use the first broker's settings (extend for multi-broker setups)
        /** @var Device $primary */
        $primary  = $devices->first();
        $settings = (new ConnectionSettings())
            ->setConnectTimeout(10)
            ->setKeepAliveInterval(60);

        if ($primary->mqtt_username) {
            $settings = $settings
                ->setUsername($primary->mqtt_username)
                ->setPassword($primary->mqtt_password ?? '');
        }

        $client = new MqttClient($primary->mqtt_host, $primary->mqtt_port, 'power-radar-subscriber-' . uniqid());
        $client->connect($settings);

        // Build a lookup map: topic -> Device
        $topicMap = [];
        foreach ($devices as $device) {
            $topic              = $device->statusTopic();
            $topicMap[$topic]   = $device;

            $client->subscribe($topic, function (string $topic, string $message) use ($device, $shellyService) {
                $this->line("[{$device->name}] Received status update");
                try {
                    $payload = json_decode($message, true);
                    if (!is_array($payload)) {
                        return;
                    }
                    $result = $shellyService->storeReading($device, $payload);
                    $this->info("  Power: {$result['power_w']} W | Relay: " . ($result['relay_on'] ? 'ON' : 'OFF'));
                } catch (\Throwable $e) {
                    Log::error("MQTT subscribe handler error: " . $e->getMessage());
                    $this->error("  Error: " . $e->getMessage());
                }
            }, 0);

            $this->line("  Subscribed to: {$topic}");
        }

        $this->info('Listening for messages… (Ctrl+C to stop)');

        // Loop indefinitely
        $client->loop(true);

        return self::SUCCESS;
    }
}
