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

class ShellyMqttService
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
     * Send a relay on/off command via MQTT RPC.
     *
     * @throws MqttClientException
     */
    public function sendRelayCommand(Device $device, bool $on): void
    {
        $client = $this->connect($device);

        $payload = json_encode([
            'id'     => 1,
            'src'    => 'power-radar',
            'method' => 'Switch.Set',
            'params' => ['id' => 0, 'on' => $on],
        ]);

        $client->publish($device->rpcTopic(), $payload, 0);
        $client->disconnect();

        // Update device relay state
        $device->relay_state = $on;
        $device->save();

        Log::info("Shelly relay command sent", [
            'device'   => $device->shelly_id,
            'state'    => $on ? 'on' : 'off',
        ]);
    }

    /**
     * Poll the device status via MQTT and store the reading.
     * Returns the parsed status array or null on failure.
     *
     * @throws MqttClientException
     */
    public function pollStatus(Device $device): ?array
    {
        $statusPayload = null;
        $client        = $this->connect($device);

        // Subscribe to status topic
        $client->subscribe($device->statusTopic(), function (string $topic, string $message) use (&$statusPayload) {
            $statusPayload = json_decode($message, true);
        }, 0);

        // Request a fresh status report via RPC
        $rpcPayload = json_encode([
            'id'     => 2,
            'src'    => 'power-radar',
            'method' => 'Switch.GetStatus',
            'params' => ['id' => 0],
        ]);
        $client->publish($device->rpcTopic(), $rpcPayload, 0);

        // Listen for up to 5 seconds for a reply
        $client->loop(true, true, 5);
        $client->disconnect();

        if (!$statusPayload) {
            return null;
        }

        return $this->storeReading($device, $statusPayload);
    }

    /**
     * Parse and store a Shelly status payload (Gen3 format).
     */
    public function storeReading(Device $device, array $status): array
    {
        // Gen3 Switch.GetStatus / status/switch:0 format
        $powerW    = (float) ($status['apower']   ?? $status['power']   ?? 0);
        $voltageV  = (float) ($status['voltage']  ?? null);
        $currentA  = (float) ($status['current']  ?? null);
        $energyWh  = (float) ($status['aenergy']['total']  ?? $status['energy'] ?? 0);
        $energyKwh = round($energyWh / 1000, 6);
        $pf        = isset($status['pf'])          ? (float) $status['pf']         : null;
        $tempC     = isset($status['temperature']['tC']) ? (float) $status['temperature']['tC'] : null;
        $relayOn   = (bool) ($status['output'] ?? $status['ison'] ?? false);

        $reading = PowerReading::create([
            'device_id'    => $device->id,
            'power_w'      => $powerW,
            'voltage_v'    => $voltageV ?: null,
            'current_a'    => $currentA ?: null,
            'energy_kwh'   => $energyKwh,
            'pf'           => $pf,
            'temperature_c'=> $tempC,
            'recorded_at'  => now(),
        ]);

        // Update device
        $device->update([
            'relay_state'  => $relayOn,
            'last_seen_at' => now(),
        ]);

        // Update power units balance and check cut-off
        $this->updateUnitsAndCheckCutoff($device, $energyKwh);

        return [
            'reading'   => $reading,
            'power_w'   => $powerW,
            'relay_on'  => $relayOn,
            'energy_kwh'=> $energyKwh,
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

            // Compute consumed kWh since the last reset / top-up
            $consumedSinceReset = max(0, $currentEnergyKwh - (float) $unit->energy_kwh_at_last_reset);

            // If the device has been replaced or reset, cumulative energy may have dropped
            if ($currentEnergyKwh < (float) $unit->energy_kwh_at_last_reset) {
                $unit->energy_kwh_at_last_reset = $currentEnergyKwh;
                $consumedSinceReset             = 0;
            }

            // Already recorded up to the previous reading's energy – only count new consumption
            $newConsumption = max(0, $consumedSinceReset - (float) $unit->total_consumed_kwh);

            if ($newConsumption > 0) {
                $unit->balance_kwh       = max(0, (float) $unit->balance_kwh - $newConsumption);
                $unit->total_consumed_kwh = (float) $unit->total_consumed_kwh + $newConsumption;

                UnitTransaction::create([
                    'device_id'        => $device->id,
                    'type'             => 'consumption',
                    'amount_kwh'       => -$newConsumption,
                    'balance_after_kwh'=> $unit->balance_kwh,
                    'note'             => 'Auto-deducted from meter reading',
                ]);
            }

            // Check auto cut-off
            if ($device->auto_cutoff_enabled && !$unit->is_cutoff && (float) $unit->balance_kwh <= 0) {
                Log::warning("Device {$device->shelly_id}: units exhausted – cutting off relay");
                $unit->is_cutoff = true;
                $unit->cutoff_at = now();
                $unit->save();

                // Send cut-off command (best-effort)
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

            $unit->balance_kwh          = (float) $unit->balance_kwh + $kwhAmount;
            $unit->total_purchased_kwh  = (float) $unit->total_purchased_kwh + $kwhAmount;

            // Re-enable relay if it was cut off
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
                'device_id'        => $device->id,
                'type'             => 'purchase',
                'amount_kwh'       => $kwhAmount,
                'balance_after_kwh'=> $unit->balance_kwh,
                'note'             => $note ?: "Top-up of {$kwhAmount} kWh",
            ]);

            return $unit->fresh();
        });
    }
}
