<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogController extends Controller
{
    /**
     * Handle incoming error logs from POS devices.
     */
    public function error(Request $request)
    {
        $request->validate([
            'error' => 'required|string',
            'stack_trace' => 'nullable|string',
            'device_info' => 'nullable|array',
            'app_version' => 'nullable|string',
        ]);

        if (config('app.env') !== 'production' && ! config('services.discord.allow_non_prod', false)) {
            return response()->json(['message' => 'Logs are only forwarded to Discord in production.'], 200);
        }

        $webhookUrl = config('services.discord.webhook_url', env('LOG_DISCORD_WEBHOOK_URL'));

        if (! $webhookUrl) {
            Log::warning('Discord Webhook URL is not configured.');

            return response()->json(['message' => 'Discord Webhook URL is not configured.'], 500);
        }

        $cleanWebhookUrl = Str::before($webhookUrl, '/slack');

        $device = $request->user(); // Assuming authenticated via sanctum (pos.device middleware)

        $embed = [
            'title' => '🚨 POS Client Error',
            'color' => hexdec('ff0000'), // Red
            'fields' => [
                [
                    'name' => 'Device ID / Name',
                    'value' => $device ? "{$device->id} / {$device->name}" : 'Unknown',
                    'inline' => true,
                ],
                [
                    'name' => 'App Version',
                    'value' => $request->input('app_version', 'Unknown'),
                    'inline' => true,
                ],
                [
                    'name' => 'Error',
                    'value' => substr($request->input('error'), 0, 1024),
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        if ($request->filled('stack_trace')) {
            $embed['fields'][] = [
                'name' => 'Stack Trace',
                'value' => '```'.substr($request->input('stack_trace'), 0, 1018).'```',
            ];
        }

        if ($request->filled('device_info')) {
            $embed['fields'][] = [
                'name' => 'Device Info',
                'value' => '```json'."\n".substr(json_encode($request->input('device_info'), JSON_PRETTY_PRINT), 0, 1010)."\n".'```',
            ];
        }

        try {
            Http::post($cleanWebhookUrl, [
                'embeds' => [$embed],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send POS error to Discord: '.$e->getMessage());
        }

        return response()->json(['message' => 'Error logged successfully.'], 200);
    }
}
