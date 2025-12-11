<?php

namespace App\Http\Controllers\Dashboard\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\Product\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $merchantId = auth()->user()->merchant_id;

        $categories = ProductCategory::globalAndCurrentMerchant()
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

        return Inertia::render('Dashboard/Product/Categories/Index', [
            'categories' => $categories,
            'filters'    => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        $merchantId = auth()->user()->merchant_id;
        $categories = ProductCategory::globalAndCurrentMerchant()
            ->where(function ($query) {
                $query->whereDoesntHave('parent.parent')
                    ->orWhereNull('parent_id');
            })
            ->get();

        return Inertia::render('Dashboard/Product/Categories/Create', [
            'availableCategories' => $categories,
        ]);
    }

    public function store(StoreProductCategoryRequest $request)
    {
        $merchantId = auth()->user()->merchant_id;

        ProductCategory::create([
            'merchant_id' => $merchantId,
            'name'        => $request->name,
            'parent_id'   => $request->parent_id,
            'is_active'   => true,
        ]);

        return redirect()->route('dashboard.products.categories.index')->with('success', 'Kategori produk berhasil ditambahkan.');
    }

    public function edit(ProductCategory $category)
    {
        $this->authorize('update', $category);

        $merchantId          = auth()->user()->merchant_id;
        $availableCategories = ProductCategory::globalAndCurrentMerchant()
            ->where('id', '!=', $category->id)
            ->where(function ($query) {
                $query->whereDoesntHave('parent.parent')
                    ->orWhereNull('parent_id');
            })
            ->get();

        return Inertia::render('Dashboard/Product/Categories/Edit', [
            'category'            => $category,
            'availableCategories' => $availableCategories,
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category)
    {
        $this->authorize('update', $category);

        $category->update([
            'name'      => $request->name,
            'parent_id' => $request->parent_id,
        ]);

        return redirect()->route('dashboard.products.categories.index')->with('success', 'Kategori produk berhasil diperbarui.');
    }

    public function destroy(ProductCategory $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()->route('dashboard.products.categories.index')->with('success', 'Kategori produk berhasil dihapus (soft delete).');
    }

    public function forceDelete(ProductCategory $category)
    {
        $this->authorize('forceDelete', $category);

        $category->forceDelete();

        return redirect()->route('dashboard.products.categories.index')->with('success', 'Kategori produk berhasil dihapus permanen.');
    }
}
