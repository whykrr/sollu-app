<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;
use Storage;

class SettingController extends Controller
{
    public function __construct()
    {
        Gate::authorize('setting', 'cms');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = Setting::whereIn('key', ['website', 'social_media', 'system'])->get();
        return inertia('Setting/Index', [
            'website' => $settings->where('key', '=', 'website')->first(),
            'socialMedia' => $settings->where('key', '=', 'social_media')->first(),
            'system' => $settings->where('key', '=', 'system')->first(),
        ]);
    }

    public function update(Request $request, Setting $setting)
    {
        $value = [];

        switch ($setting->key) {
            case 'website':
                Gate::authorize('setting_website', 'cms');

                $request->validate([
                    'name' => ['required', 'max:100'],
                    'logo' => ['nullable', File::image()->max(3 * 1024)],
                    'icon' => ['nullable', File::image()->max(1024), Rule::dimensions()->ratio(1 / 1)],
                    'multiple_language' => 'boolean'
                ]);

                $value = [
                    'name' => $request->post('name') ?? "",
                    'address' => $request->post('address') ?? "",
                    'multiple_language' => (bool) $request->post('multiple_language')
                ];

                if ($logo = $request->hasFile('logo')) {
                    $logo = $request->file("logo");
                    $oldFile = $setting->value['logo'];
                    if ($oldFile != null) {
                        Storage::disk('public')->delete($oldFile);
                    }

                    $value['logo'] = $logo->storeAs('setting', 'logo.' . $logo->getClientOriginalExtension(), 'public');
                } else {
                    $value['logo'] = $setting->value['logo'];
                }

                if ($request->hasFile('icon')) {
                    $icon = $request->file("icon");
                    $oldFile = $setting->value['icon'];
                    if ($oldFile != null) {
                        Storage::disk('public')->delete($oldFile);
                    }

                    $value['icon'] = $icon->storeAs('setting', 'icon.' . $icon->getClientOriginalExtension(), 'public');
                } else {
                    $value['icon'] = $setting->value['icon'];
                }

                break;

            case 'social_media':
                $request->validate([
                    'facebook' => ['nullable', 'url'],
                    'instagram' => ['nullable', 'url'],
                    'x' => ['nullable', 'url'],
                    'youtube' => ['nullable', 'url'],
                    'tiktok' => ['nullable', 'url'],
                    'whatsapp' => ['nullable', 'regex:/^[0-9]{8,15}$/']
                ]);

                $value = [
                    'facebook' => $request->post('facebook') ?? "",
                    'instagram' => $request->post('instagram') ?? "",
                    'x' => $request->post('x') ?? "",
                    'youtube' => $request->post('youtube') ?? "",
                    'tiktok' => $request->post('tiktok') ?? "",
                    'whatsapp' => $request->post('whatsapp') ?? "",
                ];

                break;

            default:
                $value['language'] = $request->post('language');
                Cache::delete('system-locale');
                break;
        }

        $setting->update([
            'value' => $value
        ]);

        Cache::forever($setting->key, json_encode($setting->value));

        return redirect()->route('admin.settings.index')->with('success', 'data was updated!');
    }
}
