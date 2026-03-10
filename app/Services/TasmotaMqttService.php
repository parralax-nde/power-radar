<?php

namespace App\Services;

use App\Models\Device;
use App\Models\PowerReading;
use App\Models\PowerUnit;
use App\Models\UnitTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;

class TasmotaMqttService
{
    /**
     * Build an MQTT client for the given device and connect.
     */
    public function connect(Device $device): MqttClient
    {
        $settings = (new ConnectionSettings())
            ->setConnectTimeout(10)
            ->setSocketTimeout(10)
            ->setKeepAliveInterval(60)
            ->setResendTimeout(10);

        if ($device->mqtt_username) {
            $settings = $settings
                ->setUsername($device->mqtt_username)
                ->setPassword($device->mqtt_password ?? '');
        }

        $clientId = 'power-radar-' . $device->id . '-' . uniqid();
        $client   = new MqttClient($device->mqtt_host, $device->mqtt_port, $clientId);
        $client->connect($settings);

        return $client;
    }

    /**
     * Send a relay on/off command via Tasmota MQTT.
     * Publishes "ON" or "OFF" to cmnd/{device_id}/Power.
     *
     * @throws MqttClientException
     */
    public function sendRelayCommand(Device $device, bool $on): void
    {
        $client  = $this->connect($device);
        $payload = $on ? 'ON' : 'OFF';
        $topic   = "cmnd/{$device->shelly_id}/Power";

        $client->publish($topic, $payload, 0);
        $client->disconnect();

        $device->relay_state = $on;
        $device->save();

        Log::info("Tasmota relay command sent", [
            'device' => $device->shelly_id,
            'state'  => $on ? 'on' : 'off',
        ]);
    }

    /**
     * Poll the device status via MQTT and store the reading.
     * Subscribes to tele/{device_id}/SENSOR and requests a status update.
     *
     * @throws MqttClientException
     */
    public function pollStatus(Device $device): ?array
    {
        $statusPayload = null;
        $client        = $this->connect($device);

        // Subscribe to the Tasmota SENSOR telemetry topic
        $sensorTopic = "tele/{$device->shelly_id}/SENSOR";
        $client->subscribe($sensorTopic, function (string $topic, string $message) use (&$statusPayload) {
            $decoded = json_decode($message, true);
            if (is_array($decoded)) {
                $statusPayload = $decoded;
            }
        }, 0);

        // Also subscribe to stat/RESULT to get relay state
        $resultTopic = "stat/{$device->shelly_id}/RESULT";
        $client->subscribe($resultTopic, function (string $topic, string $message) use (&$statusPayload) {
            $decoded = json_decode($message, true);
            if (is_array($decoded) && $statusPayload !== null) {
                $statusPayload = array_merge($statusPayload, $decoded);
            }
        }, 0);

        // Request a fresh telemetry report via Status 8 (energy) command
        $client->publish("cmnd/{$device->shelly_id}/Status", '8', 0);

        // Listen for up to 5 seconds for a reply
        $client->loop(true, true, 5);
        $client->disconnect();

        if (!$statusPayload) {
            return null;
        }

        return $this->storeReading($device, $statusPayload);
    }

    /**
     * Parse and store a Tasmota SENSOR payload.
     *
     * Tasmota tele/TOPIC/SENSOR format:
     * {
     *   "ENERGY": {
     *     "Power": 150, "ApparentPower": 165, "ReactivePower": 62,
     *     "Factor": 0.91, "Voltage": 231, "Current": 0.65,
     *     "Today": 1.234, "Yesterday": 2.345, "Total": 45.678
     *   }
     * }
     */
    public function storeReading(Device $device, array $status): array
    {
        // Handle both direct ENERGY and nested StatusSNS.ENERGY formats
        $energy   = $status['ENERGY'] ?? $status['StatusSNS']['ENERGY'] ?? [];

        $powerW    = (float) ($energy['Power']   ?? 0);
        $voltageV  = (float) ($energy['Voltage'] ?? 0) ?: null;
        $currentA  = (float) ($energy['Current'] ?? 0) ?: null;
        $totalKwh  = (float) ($energy['Total']   ?? 0);
        $pf        = isset($energy['Factor']) ? (float) $energy['Factor'] : null;

        // Tasmota tracks cumulative total in kWh directly
        $energyKwh = round($totalKwh, 6);

        // Relay state from POWER field (if present in merged payload)
        $relayOn = isset($status['POWER']) ? strtoupper((string) $status['POWER']) === 'ON' : true;

        $reading = PowerReading::create([
            'device_id'     => $device->id,
            'power_w'       => $powerW,
            'voltage_v'     => $voltageV,
            'current_a'     => $currentA,
            'energy_kwh'    => $energyKwh,
            'pf'            => $pf,
            'temperature_c' => null,
            'recorded_at'   => now(),
        ]);

        $device->update([
            'relay_state'  => $relayOn,
            'last_seen_at' => now(),
        ]);

        $this->updateUnitsAndCheckCutoff($device, $energyKwh);

        return [
            'reading'    => $reading,
            'power_w'    => $powerW,
            'relay_on'   => $relayOn,
            'energy_kwh' => $energyKwh,
        ];
    }

    /**
     * Update the device's unit balance based on energy consumed since last reading.
     * If balance drops to or below zero, cut the relay off.
     */
    public function updateUnitsAndCheckCutoff(Device $device, float $currentEnergyKwh): void
    {
        DB::transaction(function () use ($device, $currentEnergyKwh) {
            $unit = PowerUnit::firstOrCreate(
                ['device_id' => $device->id],
                [
                    'balance_kwh'              => 0,
                    'total_purchased_kwh'      => 0,
                    'total_consumed_kwh'       => 0,
                    'energy_kwh_at_last_reset' => $currentEnergyKwh,
                    'is_cutoff'                => false,
                ]
            );

            $consumedSinceReset = max(0, $currentEnergyKwh - (float) $unit->energy_kwh_at_last_reset);

            if ($currentEnergyKwh < (float) $unit->energy_kwh_at_last_reset) {
                $unit->energy_kwh_at_last_reset = $currentEnergyKwh;
                $consumedSinceReset             = 0;
            }

            $newConsumption = max(0, $consumedSinceReset - (float) $unit->total_consumed_kwh);

            if ($newConsumption > 0) {
                $unit->balance_kwh        = max(0, (float) $unit->balance_kwh - $newConsumption);
                $unit->total_consumed_kwh = (float) $unit->total_consumed_kwh + $newConsumption;

                UnitTransaction::create([
                    'device_id'         => $device->id,
                    'type'              => 'consumption',
                    'amount_kwh'        => -$newConsumption,
                    'balance_after_kwh' => $unit->balance_kwh,
                    'note'              => 'Auto-deducted from meter reading',
                ]);
            }

            if ($device->auto_cutoff_enabled && !$unit->is_cutoff && (float) $unit->balance_kwh <= 0) {
                Log::warning("Device {$device->shelly_id}: units exhausted – cutting off relay");
                $unit->is_cutoff = true;
                $unit->cutoff_at = now();
                $unit->save();

                try {
                    $this->sendRelayCommand($device, false);
                } catch (\Throwable $e) {
                    Log::error("Failed to send cutoff command: " . $e->getMessage());
                }
            } else {
                $unit->save();
            }
        });
    }

    /**
     * Top up units for a device.
     */
    public function topUp(Device $device, float $kwhAmount, string $note = ''): PowerUnit
    {
        return DB::transaction(function () use ($device, $kwhAmount, $note) {
            $unit = PowerUnit::firstOrCreate(
                ['device_id' => $device->id],
                [
                    'balance_kwh'              => 0,
                    'total_purchased_kwh'      => 0,
                    'total_consumed_kwh'       => 0,
                    'energy_kwh_at_last_reset' => 0,
                ]
            );

            $unit->balance_kwh         = (float) $unit->balance_kwh + $kwhAmount;
            $unit->total_purchased_kwh = (float) $unit->total_purchased_kwh + $kwhAmount;

            if ($unit->is_cutoff) {
                $unit->is_cutoff = false;
                $unit->cutoff_at = null;
                $unit->save();

                try {
                    $this->sendRelayCommand($device, true);
                } catch (\Throwable $e) {
                    Log::error("Failed to restore relay after top-up: " . $e->getMessage());
                }
            } else {
                $unit->save();
            }

            UnitTransaction::create([
                'device_id'         => $device->id,
                'type'              => 'purchase',
                'amount_kwh'        => $kwhAmount,
                'balance_after_kwh' => $unit->balance_kwh,
                'note'              => $note ?: "Top-up of {$kwhAmount} kWh",
            ]);

            return $unit->fresh();
        });
    }
}
