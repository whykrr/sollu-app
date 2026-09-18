<?php

namespace App\Http\Controllers\App\Master;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Master\Category\ReorderCategoryRequest;
use App\Http\Requests\App\Master\Category\StoreCategoryRequest;
use App\Http\Requests\App\Master\Category\UpdateCategoryRequest;
use App\Models\Master\ProductCategory;
use App\Services\App\Master\CategoryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize(PermissionEnum::CATEGORY_VIEW->value);

        $categories = $this->categoryService->getTree();

        return Inertia::render('Master/Product/Category/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::CATEGORY_CREATE->value);

        try {
            $this->categoryService->create($request->validated());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::CREATE_SUCCESS
            );
        } catch (Exception $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                'Terjadi kesalahan: '.$e->getMessage()
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $this->authorize(PermissionEnum::CATEGORY_UPDATE->value);

        try {
            $this->categoryService->update($category, $request->validated());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::UPDATE_SUCCESS
            );
        } catch (Exception $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                'Terjadi kesalahan: '.$e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $category): RedirectResponse
    {
        $this->authorize(PermissionEnum::CATEGORY_DELETE->value);

        try {
            $this->categoryService->delete($category);

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::DELETE_SUCCESS
            );
        } catch (Exception $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }
    }

    /**
     * Reorder categories via drag and drop.
     */
    public function reorder(ReorderCategoryRequest $request): JsonResponse
    {
        $this->authorize(PermissionEnum::CATEGORY_UPDATE->value);

        try {
            $this->categoryService->reorder($request->validated()['categories']);

            return response()->json([
                'message' => 'Urutan berhasil diperbarui.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 422);
        }
    }
}
