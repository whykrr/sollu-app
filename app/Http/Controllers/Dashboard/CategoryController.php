<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $merchantId = auth()->user()->merchant_id;

        $categories = Category::ownedByMerchant($merchantId)
            ->with('parent')
            ->with('children.children') // Eager load up to 3 levels
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->whereNull('parent_id')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Dashboard/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function create()
    {
        $merchantId = auth()->user()->merchant_id;
        $categories = Category::ownedByMerchant($merchantId)
            ->where(function ($query) {
                $query->whereDoesntHave('parent.parent')
                      ->orWhereNull('parent_id');
            })
            ->get();

        return Inertia::render('Dashboard/Categories/Create', [
            'availableCategories' => $categories
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $merchantId = auth()->user()->merchant_id;

        Category::create([
            'merchant_id' => $merchantId,
            'name' => $request->name,
            'slug' => Str::slug($request->name . '-' . $merchantId . '-' . uniqid()),
            'parent_id' => $request->parent_id,
            'is_active' => true,
        ]);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        $this->authorize('update', $category);

        $merchantId = auth()->user()->merchant_id;
        $availableCategories = Category::ownedByMerchant($merchantId)
            ->where('id', '!=', $category->id)
            ->where(function ($query) {
                $query->whereDoesntHave('parent.parent')
                      ->orWhereNull('parent_id');
            })
            ->get();

        return Inertia::render('Dashboard/Categories/Edit', [
            'category' => $category,
            'availableCategories' => $availableCategories
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        $category->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
        ]);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus (soft delete).');
    }

    public function forceDelete(Category $category)
    {
        $this->authorize('forceDelete', $category);

        $category->forceDelete();

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus permanen.');
    }
}
