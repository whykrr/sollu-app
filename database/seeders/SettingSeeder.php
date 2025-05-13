<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'website',
                'value' => [
                    'name' => 'Sollu CMS',
                    'address' => '',
                    'logo' => '',
                    'icon' => '',
                    'multiple_language' => false
                ],
            ],
            [
                'key' => 'system',
                'value' => [
                    'language' => 'id',
                ],
            ],
            [
                'key' => 'social_media',
                'value' => [
                    'facebook' => '',
                    'instagram' => '',
                    'x' => '',
                    'youtube' => '',
                    'tiktok' => '',
                    'whatsapp' => '',
                ],
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
