<?php

namespace App\Http\Controllers\App\Master;

use App\Constants\FlashDataVariable;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Master\Recipe\StoreRecipeTemplateRequest;
use App\Http\Requests\App\Master\Recipe\UpdateRecipeTemplateRequest;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\RecipeVersion;
use App\Services\App\Master\RecipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeTemplateController extends Controller
{
    public function __construct(
        protected RecipeService $recipeService
    ) {}

    public function index(Request $request): Response
    {
        $businessId = $request->user()->business_id;

        $recipes = RecipeVersion::query()
            ->whereHas('product', fn ($q) => $q->where('business_id', $businessId))
            ->where('is_active', true)
            ->with([
                'product:id,name,code,product_category_id',
                'product.category:id,name',
                'productItem:id,product_id,name,variant_combination,sku',
                'items.productItem.uom:id,name',
                'items.productItem.inventoryItem.balances',
            ])
            ->when($request->search, function ($q, $search) {
                $q->whereHas('product', fn ($sq) => $sq->whereLike('name', "%{$search}%"))
                    ->orWhereHas('productItem', fn ($sq) => $sq->whereLike('name', "%{$search}%"));
            })
            ->latest()
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        // Append estimated HPP
        $recipes->getCollection()->transform(function ($recipe) {
            $recipe->estimated_hpp = $this->recipeService->calculateEstimatedCost($recipe);

            return $recipe;
        });

        // Available products for recipe assignment
        $products = Product::currentBusiness()
            ->where('product_type', 'basic')
            ->select('id', 'name', 'has_variant')
            ->with('productItems:id,product_id,name,variant_combination,sku')
            ->get();

        // Available raw materials
        $rawMaterials = ProductItem::currentBusiness()
            ->where('item_type', 'raw_material')
            ->where('is_active', true)
            ->with(['uom:id,name', 'inventoryItem.balances'])
            ->get();

        return Inertia::render('Master/Product/Recipe/Index', [
            'recipes' => $recipes,
            'products' => $products,
            'rawMaterials' => $rawMaterials,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $recipe = RecipeVersion::with([
            'product:id,name,business_id',
            'productItem:id,name,variant_combination',
            'items.productItem.uom',
            'items.productItem.inventoryItem.balances',
        ])->findOrFail($id);

        if ($recipe->product->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        $recipe->estimated_hpp = $this->recipeService->calculateEstimatedCost($recipe);

        return response()->json($recipe);
    }

    public function store(StoreRecipeTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::currentBusiness()->findOrFail($data['product_id']);

        $this->recipeService->syncRecipe(
            $product,
            $data['items'],
            $data['product_item_id'] ?? null
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Template resep berhasil disimpan dan versi baru aktif.'
        );
    }

    public function update(UpdateRecipeTemplateRequest $request, string $id): RedirectResponse
    {
        $currentRecipe = RecipeVersion::with('product')->findOrFail($id);
        if ($currentRecipe->product->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $data = $request->validated();
        $this->recipeService->syncRecipe(
            $currentRecipe->product,
            $data['items'],
            $data['product_item_id'] ?? $currentRecipe->product_item_id
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Versi resep berhasil diperbarui.'
        );
    }

    public function destroy(string $id): RedirectResponse
    {
        $recipe = RecipeVersion::with('product')->findOrFail($id);
        if ($recipe->product->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        $recipe->update(['is_active' => false]);

        // If no active recipes remain, update product has_recipe flag
        if (! $recipe->product->recipeVersions()->where('is_active', true)->exists()) {
            $recipe->product->update(['has_recipe' => false]);
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Template resep berhasil dinonaktifkan.'
        );
    }
}
