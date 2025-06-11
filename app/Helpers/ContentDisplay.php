<?php

namespace App\Helpers;

use App\Models\ContentType;

class ContentDisplay
{
    /**
     * multiple displaying contents
     * @param \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContentType> $contentTypes
     * @return array
     */
    public static function multiple($contentTypes)
    {
        $contents = [];
        foreach ($contentTypes as $contentType) {
            $contents[$contentType->slug] = $contentType->is_listed
                ? self::singleListed($contentType->load(['contents.field_values.content_field']))
                : self::single($contentType->load(['content.field_values.content_field']));
        }

        return $contents;
    }

    /**
     * single  displaying contents
     * @return array
     */
    public static function single(ContentType $type = null)
    {
        $contentCache = [];
        if ($type && $type->content) {
            $contentCache['title']      = $type->content->title;
            $contentCache['slug']       = $type->content->slug;
            $contentCache['created_at'] = $type->content->created_at;
            foreach ($type->content->field_values as $fv) {
                $contentCache[$fv->content_field->key] = $fv->value;
            }
            $contentCache['meta'] = [
                'title'       => $type->content->meta_title,
                'keyword'     => $type->content->meta_keyword,
                'description' => $type->content->meta_description,
            ];
        }

        return $contentCache;
    }

    /**
     * single listed displaying contents
     * @return array
     */
    public static function singleListed(ContentType $type = null)
    {
        $contentsCache = [];
        if ($type && count($type->contents) > 0) {
            foreach ($type->contents as $content) {
                $contentCache['title']      = $content->title;
                $contentCache['slug']       = $content->slug;
                $contentCache['created_at'] = $content->created_at;
                foreach ($content->field_values as $fv) {
                    $contentCache[$fv->content_field->key] = $fv->value;
                }
                $contentCache['meta'] = [
                    'title'       => $content->meta_title,
                    'keyword'     => $content->meta_keyword,
                    'description' => $content->meta_description,
                ];
                $contentsCache[] = $contentCache;
            }
        }

        return $contentsCache;
    }
}
