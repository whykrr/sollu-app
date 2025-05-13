<?php

namespace App\Http\Controllers\Admin;

use Cache;
use Carbon\Carbon;
use App\Models\ContentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\ContentField;

class ContentTypeController extends Controller
{
    public function __construct()
    {
        Gate::authorize('content_type', 'cms');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia('ContentType/Index', [
            'types' => ContentType::paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('ContentType/Input', [
            'parents' => ContentType::parent()->get()
        ]);
    }

    private function _getCustomRules(Request $request): array
    {
        $customRules = [];

        foreach ($request->post('fields') as $idx => $field) {
            $request->has("fields.$idx.validation.max")
                ? $customRules["fields.$idx.validation.max"] = ['required', 'numeric']
                : null;

            $request->has("fields.$idx.validation.min")
                ? $customRules["fields.$idx.validation.min"] = ['required', 'numeric']
                : null;

            $request->has("fields.$idx.validation.size")
                ? $customRules["fields.$idx.validation.size"] = ['required', 'numeric']
                : null;

            $request->has("fields.$idx.validation.dimension.width")
                ? $customRules["fields.$idx.validation.dimension.width"] = ['required', 'numeric']
                : null;

            $request->has("fields.$idx.validation.dimension.height")
                ? $customRules["fields.$idx.validation.dimension.height"] = ['required', 'numeric']
                : null;

            $request->has("fields.$idx.validation.ratio")
                ? $customRules["fields.$idx.validation.ratio"] = ['required']
                : null;
        }

        return $customRules;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $customRules = $this->_getCustomRules($request);

        $request->validate([
            'name' => ['required', 'max:255', 'unique:content_types'],
            'fields.*.name' => ['required', 'max:100'],
            'max_row' => ['required_if_accepted:is_listed', 'numeric', 'nullable', 'max:100'],
            'parent_id' => ['required_if_accepted:have_parent'],
            'is_listed' => ['required_if_accepted:have_parent', 'nullable'],
            ...$customRules
        ]);

        $contentType = ContentType::make([
            'parent_id' => $request->post('parent_id'),
            'name' => $request->post('name'),
            'description' => $request->post('description'),
            'is_listed' => $request->post('is_listed'),
            'max_row' => $request->post('max_row'),
            'title_aliases' => $request->post('title_aliases'),
            'with_meta' => $request->post('with_meta'),
        ]);

        $contentType->save();

        $contentFields = array_map(function ($item) {
            return [
                'name' => $item['name'],
                'field_type' => $item['field_type'],
                'is_required' => $item['is_required'],
                'validation' => $item['validation'],
            ];
        }, $request->input('fields'));

        $contentType->content_fields()->createMany($contentFields);

        Cache::delete('content-sidebar');

        return redirect()->route('admin.content-types.index')->with('success', 'data was created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(ContentType $contentType)
    {
        return inertia('ContentType/Input', [
            'type' => $contentType->load('content_fields'),
            'parents' => ContentType::parent()->get()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ContentType $contentType)
    {

        $customRules = $this->_getCustomRules($request);

        $request->validate([
            'name' => ['required', 'max:255', Rule::unique('content_types')->ignore($contentType->id)],
            'fields.*.name' => ['required', 'max:100'],
            'max_row' => ['required_if_accepted:is_listed', 'numeric', 'nullable'],
            'parent_id' => ['required_if_accepted:have_parent'],
            ...$customRules
        ]);

        $contentType->update([
            'parent_id' => $request->post('parent_id'),
            'name' => $request->post('name'),
            'description' => $request->post('description'),
            'is_listed' => $request->post('is_listed'),
            'max_row' => $request->post('max_row'),
            'title_aliases' => $request->post('title_aliases'),
            'with_meta' => $request->post('with_meta'),
        ]);

        $modelContentFields = $contentType->content_fields()
            ->findMany($request->input('fields.*.id'));

        $contentFields = array_map(function ($item) use ($contentType, $modelContentFields) {
            if (isset($item['id'])) {
                $contentField = $modelContentFields->find($item['id']);
                return $contentField->fill([
                    'name' => $item['name'],
                    'field_type' => $item['field_type'],
                    'is_required' => $item['is_required'],
                    'validation' => $item['validation'],
                ]);
            }

            return new ContentField([
                'content_type_id' => $contentType->id,
                'name' => $item['name'],
                'field_type' => $item['field_type'],
                'is_required' => $item['is_required'],
                'validation' => $item['validation'],
            ]);
        }, $request->input('fields'));

        $contentType->content_fields()->saveMany($contentFields);
        Cache::delete('content-sidebar');

        return redirect()->route('admin.content-types.index')->with('success', 'data was updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContentType $contentType)
    {
        $contentType->content_fields()->delete();
        $contentType->delete();
        Cache::delete('content-sidebar');

        return redirect()->route('admin.content-types.index')->with('success', 'data was deleted!');
    }

    public function destroyField(ContentType $contentType, $id)
    {
        $contentType->content_fields()->where('id', $id)->delete();
        Cache::delete('content-sidebar');

        return redirect()->back()->with('success', 'data field was deleted!');
    }
}
