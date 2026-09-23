<?php

namespace App\Listeners\Discord;

use App\Events\User\BusinessRegistered;
use App\Services\Discord\DiscordWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class SendNewMerchantDiscordNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected DiscordWebhookService $discordService
    ) {}

    public function handle(BusinessRegistered $event): void
    {
        $business = $event->business->loadMissing('type');
        $user = $event->user;
        $outlet = $event->outlet;

        $phone = $business->phone ?? $user->phone ?? '-';
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $waPhone = '62'.substr($cleanPhone, 1);
        } else {
            $waPhone = $cleanPhone;
        }

        $phoneDisplay = $phone !== '-'
            ? "[{$phone}](https://wa.me/{$waPhone})"
            : '-';

        $trialEnd = $business->trial_end_at
            ? \Carbon\Carbon::parse($business->trial_end_at)->format('d M Y').' (15 Hari)'
            : '-';

        $cockpitUrl = route('cockpit.merchants.show', $business->id, false);
        $fullCockpitUrl = url($cockpitUrl);

        $embed = [
            'title' => '🏪 Merchant Baru Mendaftar!',
            'url' => $fullCockpitUrl,
            'description' => "Seorang pengguna baru saja mendaftarkan bisnis di **Sollu Indonesia**.\n\n👉 **[Buka Detail Bisnis di Cockpit]({$fullCockpitUrl})**",
            'color' => hexdec('10B981'), // Emerald Green
            'fields' => [
                [
                    'name' => 'Nama Bisnis',
                    'value' => "**{$business->name}**",
                    'inline' => true,
                ],
                [
                    'name' => 'Tipe Bisnis',
                    'value' => $business->type?->name ?? '-',
                    'inline' => true,
                ],
                [
                    'name' => 'Nama Pemilik',
                    'value' => $business->owner_name ?? $user->name,
                    'inline' => true,
                ],
                [
                    'name' => 'No. WhatsApp / Telepon',
                    'value' => $phoneDisplay,
                    'inline' => true,
                ],
                [
                    'name' => 'Email',
                    'value' => $business->email ?? $user->email ?? '-',
                    'inline' => true,
                ],
                [
                    'name' => 'Outlet Utama',
                    'value' => $outlet->name ?? '-',
                    'inline' => true,
                ],
                [
                    'name' => 'Masa Uji Coba (Trial)',
                    'value' => $trialEnd,
                    'inline' => true,
                ],
                [
                    'name' => 'ID Bisnis',
                    'value' => "`{$business->id}`",
                    'inline' => true,
                ],
                [
                    'name' => '⚡ Aksi',
                    'value' => "👉 **[Buka Detail Bisnis di Cockpit]({$fullCockpitUrl})**",
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'Sollu Admin System',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $components = [
            [
                'type' => 1, // Action Row
                'components' => [
                    [
                        'type' => 2, // Button
                        'style' => 5, // Link Button
                        'label' => '🏢 Buka Detail Bisnis di Cockpit',
                        'url' => $fullCockpitUrl,
                    ],
                ],
            ],
        ];

        $this->discordService->send('merchant_registration', $embed, $components);
    }

    public function failed(BusinessRegistered $event, Throwable $exception): void
    {
        // Handled silently with logger inside DiscordWebhookService
    }
}
