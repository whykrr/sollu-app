<?php

namespace App\Http\Controllers\Admin;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class LanguageController extends Controller
{
    public function __construct()
    {
        Gate::authorize('language', 'cms');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return inertia('Language/Index', [
            'languages' => Language::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Language/Input');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $language = Language::make($request->validate([
            'code' => ['required', 'unique:languages', 'max:10'],
            'name' => ['required', 'max:100']
        ]));

        $language->save();

        return redirect()->route('admin.languages.index')->with('success', 'data was created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Language $language)
    {
        return inertia('Language/Input', [
            'language' => $language
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Language $language)
    {
        $language->update($request->validate(
            [
                'code' => ['required', 'max:10', Rule::unique('languages')->ignore($language->id)],
                'name' => ['required', 'max:100'],
            ]
        ));

        return redirect()->route('admin.languages.index')->with('success', 'data was updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Language $language)
    {
        $language->delete();

        return redirect()->route('admin.languages.index')->with('success', 'data was deleted!');
    }

    public function setDefault(Language $language)
    {
        $language->update(
            [
                'is_default' => true
            ]
        );

        $language->except($language)
            ->update(['is_default' => false]);

        return redirect()->route('admin.languages.index')->with('success', 'data was updated!');
    }
}
