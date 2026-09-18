<?php

namespace App\Services\App\Master;

use App\Models\Master\ProductCategory;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Get categories as a tree (max 1 level depth).
     *
     * @return Collection<int, ProductCategory>
     */
    public function getTree(): Collection
    {
        return ProductCategory::query()
            ->currentBusiness()
            ->whereNull('parent_id')
            ->with([
                'children' => function ($query) {
                    $query->orderBy('sort_order', 'asc');
                },
            ])
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    /**
     * Create a new product category.
     */
    public function create(array $data, ?string $businessId = null): ProductCategory
    {
        $data['business_id'] = $businessId ?? Auth::user()?->business_id ?? $data['business_id'] ?? null;

        if (! isset($data['sort_order'])) {
            $maxSortOrder = ProductCategory::query()
                ->where('business_id', $data['business_id'])
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('sort_order');
            $data['sort_order'] = $maxSortOrder ? $maxSortOrder + 1 : 1;
        }

        $category = ProductCategory::create($data);
        $this->auditLogService->log(
            $category->business_id,
            'category',
            $category->id,
            'created',
            null,
            $category->toArray()
        );

        return $category;
    }

    /**
     * Update an existing product category.
     */
    public function update(ProductCategory $category, array $data): ProductCategory
    {
        $before = $category->toArray();
        $category->update($data);
        $this->auditLogService->log(
            $category->business_id,
            'category',
            $category->id,
            'updated',
            $before,
            $category->fresh()->toArray()
        );

        return $category;
    }

    /**
     * Delete a product category and its children.
     *
     * @throws Exception
     */
    public function delete(ProductCategory $category): void
    {
        // Validation: Category cannot be deleted if there are active products.
        // Also check products in child categories if this is a parent category.
        $hasActiveProducts = $category->products()->exists();

        if (! $hasActiveProducts && $category->children()->exists()) {
            $childIds = $category->children()->pluck('id');
            $hasActiveProducts = DB::table('products')
                ->whereIn('product_category_id', $childIds)
                ->whereNull('deleted_at')
                ->exists();
        }

        if ($hasActiveProducts) {
            throw new Exception('Kategori tidak dapat dihapus karena masih digunakan oleh produk.');
        }

        DB::transaction(function () use ($category) {
            // Delete and log children first
            foreach ($category->children as $child) {
                $childBefore = $child->toArray();
                $child->delete();
                $this->auditLogService->log(
                    $child->business_id,
                    'category',
                    $child->id,
                    'deleted',
                    $childBefore,
                    null
                );
            }

            // Then delete parent
            $before = $category->toArray();
            $category->delete();
            $this->auditLogService->log(
                $category->business_id,
                'category',
                $category->id,
                'deleted',
                $before,
                null
            );
        });
    }

    /**
     * Reorder product categories.
     */
    public function reorder(array $categoriesData, ?string $businessId = null): void
    {
        $targetBusinessId = $businessId ?? Auth::user()?->business_id;

        DB::transaction(function () use ($categoriesData, $targetBusinessId) {
            foreach ($categoriesData as $catData) {
                // Ensure max 1 level depth check for safety
                if (! empty($catData['parent_id'])) {
                    $parent = ProductCategory::find($catData['parent_id']);
                    if ($parent && $parent->parent_id !== null) {
                        continue;
                    }
                }

                ProductCategory::query()
                    ->where('id', $catData['id'])
                    ->where('business_id', $targetBusinessId)
                    ->update([
                        'parent_id' => $catData['parent_id'] ?? null,
                        'sort_order' => $catData['sort_order'],
                    ]);
            }
        });
    }
}
