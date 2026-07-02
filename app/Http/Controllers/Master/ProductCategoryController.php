<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Category\ReorderCategoryRequest;
use App\Http\Requests\Master\Category\StoreCategoryRequest;
use App\Http\Requests\Master\Category\UpdateCategoryRequest;
use App\Models\Master\ProductCategory;
use App\Services\Master\CategoryService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductCategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = $this->categoryService->getTree();

        return Inertia::render('Master/Product/Category/Index', [
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $this->categoryService->create($request->validated());
            return redirect()->back()->with('success', 'Kategori berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, ProductCategory $category)
    {
        try {
            $this->categoryService->update($category, $request->validated());
            return redirect()->back()->with('success', 'Kategori berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $category)
    {
        try {
            $this->categoryService->delete($category);
            return redirect()->back()->with('success', 'Kategori berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reorder categories via drag and drop.
     */
    public function reorder(ReorderCategoryRequest $request)
    {
        try {
            $this->categoryService->reorder($request->validated()['categories']);
            return response()->json(['success' => true, 'message' => 'Urutan berhasil diperbarui.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 400);
        }
    }
}
