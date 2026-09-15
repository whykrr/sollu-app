<?php

namespace App\Services\Discord;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DiscordWebhookService
{
    /**
     * Send a notification to a specific Discord channel webhook.
     *
     * @param  string  $channelKey  Channel configuration key (e.g. 'merchant_registration', 'payment_validation', 'default')
     * @param  array<string, mixed>  $embed  Discord embed payload
     * @param  array<int, mixed>  $components  Action row components (e.g. link buttons)
     * @param  array{name?: string, contents?: string, path?: string, filename?: string}|null  $attachment  Optional file attachment
     */
    public function send(string $channelKey, array $embed, array $components = [], ?array $attachment = null): bool
    {
        if (config('app.env') !== 'production' && ! config('services.discord.allow_non_prod', false)) {
            Log::info("Discord notification for [{$channelKey}] skipped (non-production environment).");

            return true;
        }

        $webhookUrl = config("services.discord.channels.{$channelKey}")
            ?: config('services.discord.channels.default');

        if (! $webhookUrl) {
            Log::warning("Discord webhook URL for channel [{$channelKey}] is not configured.");

            return false;
        }

        $cleanWebhookUrl = Str::before($webhookUrl, '/slack');

        $payload = [
            'embeds' => [$embed],
        ];

        if (! empty($components)) {
            $payload['components'] = $components;
        }

        try {
            if ($attachment && (! empty($attachment['contents']) || (! empty($attachment['path']) && file_exists($attachment['path'])))) {
                $fileContents = ! empty($attachment['contents'])
                    ? $attachment['contents']
                    : file_get_contents($attachment['path']);

                $filename = $attachment['filename'] ?? 'payment_proof.jpg';

                $response = Http::timeout(10)
                    ->attach('files[0]', $fileContents, $filename)
                    ->post($cleanWebhookUrl, [
                        'payload_json' => json_encode($payload, JSON_THROW_ON_ERROR),
                    ]);
            } else {
                $response = Http::timeout(10)
                    ->asJson()
                    ->post($cleanWebhookUrl, $payload);
            }

            if (! $response->successful()) {
                Log::error("Discord webhook [{$channelKey}] failed with status {$response->status()}: {$response->body()}");

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error("Exception occurred while sending Discord notification [{$channelKey}]: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return false;
        }
    }
}
