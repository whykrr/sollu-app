<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AppCached;
use App\Models\ContentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\Content;
use DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;
use Storage;

use function PHPUnit\Framework\isNull;

class ContentController extends Controller
{
    public function showSingle(ContentType $contentType)
    {
        $contentType = $contentType->load(['content', 'content_fields.field_value']);
        $children = $contentType->children($contentType->id)->with(['contents'])->get();
        return inertia('Content/Input', [
            'contentType' => $contentType,
            'children' => $children
        ]);
    }
    public function showListed(ContentType $contentType)
    {
        return inertia('Content/Listed', [
            'contentType' => $contentType->load(['contents'])
        ]);
    }

    public function createContentListed(ContentType $contentType)
    {
        $contentType = $contentType->load('content_fields');
        return inertia('Content/Input', [
            'contentType' => $contentType,
        ]);
    }

    public function editContentListed(ContentType $contentType, $content_id)
    {
        $contentType = $contentType->load([
            'content' => function ($query) use ($content_id) {
                $query->where('id', $content_id);
            },
            'content_fields.field_value' => function ($query) use ($content_id) {
                $query->where('content_id', $content_id);
            }
        ]);
        return inertia('Content/Input', [
            'contentType' => $contentType,
        ]);
    }

    private function _getValidationField($content_fields): array
    {
        $fieldValidation = [];

        foreach ($content_fields as $idx => $field) {
            $fieldValidation["content_fields.$idx.field_value.value"] = [];

            if ($field['is_required']) {
                if ($field['field_type'] === 'image' && $field['field_value']['oldValue'] !== null) {
                    $fieldValidation["content_fields.$idx.field_value.value"][] = 'nullable';
                } else {
                    $fieldValidation["content_fields.$idx.field_value.value"][] = 'required';
                }
            } else {
                $fieldValidation["content_fields.$idx.field_value.value"][] = 'nullable';
            }

            if (isset($field['validation']) && count($field['validation']) != 0) {
                if (array_key_exists('min', $field['validation'])) {
                    $fieldValidation["content_fields.$idx.field_value.value"][] = "min:$field[validation][min]";
                }
                if (array_key_exists('min', $field['validation'])) {
                    $fieldValidation["content_fields.$idx.field_value.value"][] = "max:$field[validation][max]";
                }
                if (array_key_exists('size', $field['validation'])) {
                    $fieldValidation["content_fields.$idx.field_value.value"][] = File::type('pdf')
                        ->max($field['validation']['size']);
                }
                $ruleImage = Rule::dimensions();
                if (array_key_exists('dimension', $field['validation'])) {
                    $ruleImage
                        ->width($field['validation']['dimension']['width'])
                        ->height($field['validation']['dimension']['height']);
                }
                if (array_key_exists('ratio', $field['validation'])) {
                    $ruleImage->ratio($field['validation']['ratio']);
                }

                if (
                    array_key_exists('dimension', $field['validation'])
                    || array_key_exists('ratio', $field['validation'])
                ) {
                    $fieldValidation["content_fields.$idx.field_value.value"][] = Rule::file()->max(10 * 1024);
                    $fieldValidation["content_fields.$idx.field_value.value"][] = $ruleImage;
                }
            }
        }
        return $fieldValidation;
    }

    public function store(ContentType $contentType, Request $request)
    {
        Gate::authorize('manage_content', 'cms');

        $request->validate([
            'title' => ['required', 'max:255', 'unique:contents'],
            'meta_title' => ['max:255'],
            'meta_keyword' => ['max:255'],
            ...$this->_getValidationField($request->post('content_fields'))
        ]);

        // handle image
        $contentFields = $request->post('content_fields');
        foreach ($contentFields as $idx => $field) {
            if (
                in_array($field['field_type'], ['image', 'file']) &&
                $request->hasFile("content_fields.$idx.field_value.value")
            ) {
                $contentFields[$idx]['field_value']['value'] = $request->file("content_fields.$idx.field_value.value")
                    ->store(
                        $field['field_type'] === 'image'
                            ? 'images'
                            : 'files',
                        'public'
                    );
            }
        }

        $content = $contentType->content()->create([
            'title' => $request->post('title'),
            'prefix_slug' => 'test',
            'published' => true,
            'meta_title' => $request->post('meta_title'),
            'meta_keyword' => $request->post('meta_keyword'),
            'meta_description' => $request->post('meta_description'),
        ]);

        $content->field_values()->createMany(
            collect($contentFields)->map(function ($field) {
                return [
                    'content_field_id' => $field['id'],
                    'value' => $field['field_value']['value'],
                ];
            })->toArray()
        );

        $this->_deleteCache();

        return redirect()->route(
            $contentType->is_listed
                ? 'admin.contents.listed'
                : 'admin.contents.index',
            [
                'content_type' => $contentType->id
            ]
        )->with('success', 'data was created!');
    }

    public function update(ContentType $contentType, Content $content, Request $request)
    {
        Gate::authorize('manage_content', 'cms');

        $request->validate([
            'title' => ['required', 'max:255', Rule::unique('contents')->ignore($request->post('id'))],
            'meta_title' => ['max:255'],
            'meta_keyword' => ['max:255'],
            ...$this->_getValidationField($request->post('content_fields'))
        ]);

        // handle image
        $contentFields = $request->post('content_fields');
        foreach ($contentFields as $idx => $field) {
            if (
                in_array($field['field_type'], ['image', 'file']) &&
                $request->hasFile("content_fields.$idx.field_value.value")
            ) {
                $oldFile = $contentFields[$idx]['field_value']['oldValue'];
                if ($oldFile != null) {
                    Storage::disk('public')->delete($oldFile);
                }

                $contentFields[$idx]['field_value']['value'] = $request->file("content_fields.$idx.field_value.value")
                    ->store(
                        $field['field_type'] === 'image'
                            ? 'images'
                            : 'files',
                        'public'
                    );
            }
        }

        $content->update([
            'title' => $request->post('title'),
            'prefix_slug' => 'test',
            'published' => $request->post('published') ?? true,
            'meta_title' => $request->post('meta_title'),
            'meta_keyword' => $request->post('meta_keyword'),
            'meta_description' => $request->post('meta_description'),
        ]);

        $content->field_values()->upsert(
            collect($contentFields)->map(function ($field) {
                $value = $field['field_value']['value'];
                if (in_array($field['field_type'], ['image', 'file'])) {
                    $value ??= $field['field_value']['oldValue'];
                }

                return [
                    'id' => $field['field_value']['id'],
                    'value' => $value,
                ];
            })->toArray(),
            ['id'],
            ['value']
        );

        $this->_deleteCache();

        return redirect()->route(
            $contentType->is_listed
                ? 'admin.contents.listed'
                : 'admin.contents.index',
            [
                'content_type' => $contentType->id
            ]
        )->with('success', 'data was updated!');
    }
    public function delete(ContentType $contentType, Content $content)
    {
        $content->field_values()->delete();
        $content->delete();
        $this->_deleteCache();

        return redirect()->route('admin.contents.listed', [
            'content_type' => $contentType->id
        ])->with('success', 'data was deleted!');
    }

    private function _deleteCache()
    {
        DB::table('cache')->whereLike('key', "website.%")->delete();
    }
}
