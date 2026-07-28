<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Product\StoreProductRequest;
use App\Http\Requests\Master\Product\UpdateProductRequest;
use App\Models\Master\Product;
use App\Services\Master\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $products = Product::currentBusiness()
            ->with(['category', 'prices', 'outlets', 'inventoryItems', 'images'])
            ->filters($request->only(['search', 'category', 'outlet', 'is_deleted']))
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Master/Product/Index', [
            'products'   => $products,
            'filters'    => $request->only(['search', 'category', 'outlet', 'is_deleted']),
            'categories' => \App\Models\Master\ProductCategory::currentBusiness()->get()->map(function ($row) {
                return [
                    'value' => $row->id,
                    'label' => $row->name,
                ];
            }),
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Product/Form', [
            'categories'     => \App\Models\Master\ProductCategory::currentBusiness()->get(),
            'outlets'        => \App\Models\Outlet::currentBusiness()->active()->get(),
            'modifierGroups' => \App\Models\Master\ModifierGroup::currentBusiness()->with('options')->get(),
            'inventoryItems' => \App\Models\Master\InventoryItem::currentBusiness()->get(),
            'products'       => Product::currentBusiness()->where('product_type', '!=', 'bundle')->get(), // for bundle components
            'uoms'           => \App\Models\Uom::where('status', 'active')->get(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $data                = $request->validated();
            $data['business_id'] = auth()->user()->business_id;

            $this->productService->createProduct($data);

            return redirect()->route('master.products.index')->with('success', 'Produk berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat produk: ' . $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        if ($product->business_id !== (auth()->user()->business_id)) {
            abort(403);
        }

        $product->load([
            'category',
            'variantGroups.options',
            'modifierGroups',
            'recipeVersions.items',
            'bundleItems',
            'prices',
            'outlets',
            'inventoryItems.variantGroupOptions',
            'images',
        ]);

        return Inertia::render('Master/Product/Form', [
            'product'        => $product,
            'categories'     => \App\Models\Master\ProductCategory::currentBusiness()->get(),
            'outlets'        => \App\Models\Outlet::currentBusiness()->active()->get(),
            'modifierGroups' => \App\Models\Master\ModifierGroup::currentBusiness()->with('options')->get(),
            'inventoryItems' => \App\Models\Master\InventoryItem::currentBusiness()->get(),
            'products'       => Product::currentBusiness()->where('product_type', '!=', 'bundle')->get(),
            'uoms'           => \App\Models\Uom::where('status', 'active')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        if ($product->business_id !== (auth()->user()->business_id ?? \App\Models\Business::first()->id)) {
            abort(403);
        }

        try {
            $data = $request->validated();
            $this->productService->updateProduct($product, $data);

            return redirect()->route('master.products.index')->with('success', 'Produk berhasil diupdate.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal update produk: ' . $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        if ($product->business_id !== (auth()->user()->business_id ?? \App\Models\Business::first()->id)) {
            abort(403);
        }

        $product->delete();

        return redirect()->back()->with('success', 'Produk diarsipkan.');
    }
}
