<?php

namespace App\Helpers;

use App\Models\ContentType;
use App\Models\Setting;
use Cache;

class AppCached
{
    public static function allContent()
    {
        $contentTypes = ContentType::all();
        foreach ($contentTypes as $contentType) {
            self::contentByType($contentType);
        }
    }

    public static function contentByType(ContentType $contentType)
    {
        if ($contentType->is_listed) {
            $type = $contentType->load(['contents.field_values.content_field']);

            $contentsCache = [];
            if (count($type->contents) > 0) {
                foreach ($type->contents as $content) {
                    $contentCache['title'] = $content->title;
                    foreach ($content->field_values as $fv) {
                        $contentCache[$fv->content_field->key] = $fv->value;
                    }
                    $contentCache['meta'] = [
                        'title' => $content->meta_title,
                        'keyword' => $content->meta_keyword,
                        'description' => $content->meta_description,
                    ];
                    $contentsCache[] = $contentCache;
                    Cache::forever("content.{$content->slug}", json_encode($contentCache));
                }
                Cache::forever("type.{$contentType->slug}", json_encode($contentsCache));
            }
        } else {
            $type = $contentType->load(['content.field_values.content_field']);
            if ($type->content) {
                $contentCache['title'] = $type->content->title;
                foreach ($type->content->field_values as $fv) {
                    $contentCache[$fv->content_field->key] = $fv->value;
                }
                $contentCache['meta'] = [
                    'title' => $type->content->meta_title,
                    'keyword' => $type->content->meta_keyword,
                    'description' => $type->content->meta_description,
                ];
                Cache::forever("type.{$contentType->slug}", json_encode($contentCache));
            }
        }
    }

    public static function allSetting()
    {
        $settings = Setting::all();
        foreach ($settings as $setting) {
            Cache::forever("setting.{$setting->key}", json_encode($setting->value));
        }
    }

    public static function settingByKey(Setting $setting)
    {
        Cache::forever("setting.{$setting->key}", json_encode($setting->value));
    }
}
