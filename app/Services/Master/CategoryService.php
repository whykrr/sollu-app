<?php

namespace App\Services\Master;

use App\Models\Master\ProductCategory;

class CategoryService
{
    private AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Get categories as a tree (max 1 level depth).
     */
    public function getTree()
    {
        return ProductCategory::currentBusiness()
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('sort_order', 'asc');
            }])
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function create(array $data)
    {
        $data['business_id'] = \Illuminate\Support\Facades\Auth::user()->business_id;

        if (! isset($data['sort_order'])) {
            $maxSortOrder = ProductCategory::currentBusiness()
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('sort_order');
            $data['sort_order'] = $maxSortOrder ? $maxSortOrder + 1 : 1;
        }

        $category = ProductCategory::create($data);
        $this->auditLogService->log($category->business_id, 'category', $category->id, 'created', null, $category->toArray());

        return $category;
    }

    public function update(ProductCategory $category, array $data)
    {
        $before = $category->toArray();
        $category->update($data);
        $this->auditLogService->log($category->business_id, 'category', $category->id, 'updated', $before, $category->fresh()->toArray());

        return $category;
    }

    public function delete(ProductCategory $category)
    {
        // Validation: Category cannot be deleted if there are active products.
        // Also check products in child categories if this is a parent category.
        $hasActiveProducts = $category->products()->exists();

        if (! $hasActiveProducts && $category->children()->exists()) {
            $childIds = $category->children()->pluck('id');
            $hasActiveProducts = \Illuminate\Support\Facades\DB::table('products')
                ->whereIn('product_category_id', $childIds)
                ->whereNull('deleted_at')
                ->exists();
        }

        if ($hasActiveProducts) {
            throw new \Exception('Kategori tidak dapat dihapus karena masih digunakan oleh produk.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($category) {
            // Delete and log children first
            foreach ($category->children as $child) {
                $childBefore = $child->toArray();
                $child->delete();
                $this->auditLogService->log($child->business_id, 'category', $child->id, 'deleted', $childBefore, null);
            }

            // Then delete parent
            $before = $category->toArray();
            $category->delete();
            $this->auditLogService->log($category->business_id, 'category', $category->id, 'deleted', $before, null);
        });
    }

    /**
     * Reorder product categories.
     */
    public function reorder(array $categoriesData)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($categoriesData) {
            foreach ($categoriesData as $catData) {
                // Ensure max 1 level depth check for safety
                if (! empty($catData['parent_id'])) {
                    $parent = ProductCategory::find($catData['parent_id']);
                    if ($parent && $parent->parent_id !== null) {
                        continue;
                    }
                }

                ProductCategory::where('id', $catData['id'])
                    ->where('business_id', \Illuminate\Support\Facades\Auth::user()->business_id)
                    ->update([
                        'parent_id' => $catData['parent_id'] ?? null,
                        'sort_order' => $catData['sort_order'],
                    ]);
            }
        });
    }
}
