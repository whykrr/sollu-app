<?php

namespace App\Http\Controllers\Cockpit;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfigController extends Controller
{
    public function index()
    {
        $midtransEnabled = SystemSetting::isMidtransEnabled();
        $helpCenterUrl = SystemSetting::get('help_center_url', '');
        $whatsappSupportNumber = SystemSetting::get('whatsapp_support_number', '');

        return Inertia::render('Cockpit/Config/Index', [
            'midtransEnabled' => $midtransEnabled,
            'settings' => [
                'help_center_url' => $helpCenterUrl,
                'whatsapp_support_number' => $whatsappSupportNumber,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'help_center_url' => ['nullable', 'string', 'max:500'],
            'whatsapp_support_number' => ['nullable', 'string', 'max:50'],
        ]);

        $url = $validated['help_center_url'] ?? null;
        if (! empty($url)) {
            $url = trim($url);
            if (! preg_match('~^(?:f|ht)tps?://~i', $url) && ! str_starts_with($url, '#') && ! str_starts_with($url, 'mailto:') && ! str_starts_with($url, 'tel:')) {
                $url = 'https://'.$url;
            }
        }

        $whatsapp = $validated['whatsapp_support_number'] ?? null;
        if (! empty($whatsapp)) {
            $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
            if (str_starts_with($whatsapp, '0')) {
                $whatsapp = '62'.substr($whatsapp, 1);
            }
        }

        SystemSetting::set('help_center_url', $url);
        SystemSetting::set('whatsapp_support_number', $whatsapp);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function updateFlag(Request $request)
    {
        $validated = $request->validate([
            'feature_name' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        SystemSetting::set($validated['feature_name'], $validated['enabled'] ? '1' : '0', 'payment');

        return redirect()->back()->with(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);
    }
}
