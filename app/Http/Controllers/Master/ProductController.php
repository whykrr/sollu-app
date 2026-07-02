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
        $products = Product::with(['category', 'prices', 'outlets', 'inventoryItems'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->product_type, function ($q, $type) {
                $q->where('product_type', $type);
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Master/Product/Index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Product/Form', [
            'categories'     => \App\Models\Master\ProductCategory::all(),
            'outlets'        => \App\Models\Outlet::all(),
            'modifierGroups' => \App\Models\Master\ModifierGroup::with('options')->get(),
            'inventoryItems' => \App\Models\Master\InventoryItem::all(),
            'products'       => Product::where('product_type', '!=', 'bundle')->get(), // for bundle components
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $data                = $request->validated();
            $data['business_id'] = auth()->user()->business_id ?? \App\Models\Business::first()->id;

            $this->productService->createProduct($data);

            return redirect()->route('master.products.index')->with('success', 'Produk berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat produk: ' . $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        $product->load([
            'category',
            'variantGroups.options',
            'modifierGroups',
            'recipeVersions.items',
            'bundleItems',
            'prices',
            'outlets',
            'inventoryItems',
        ]);

        return Inertia::render('Master/Product/Form', [
            'product'        => $product,
            'categories'     => \App\Models\Master\ProductCategory::all(),
            'outlets'        => \App\Models\Outlet::all(),
            'modifierGroups' => \App\Models\Master\ModifierGroup::with('options')->get(),
            'inventoryItems' => \App\Models\Master\InventoryItem::all(),
            'products'       => Product::where('product_type', '!=', 'bundle')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
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
        $product->delete();

        return redirect()->back()->with('success', 'Produk diarsipkan.');
    }
}
