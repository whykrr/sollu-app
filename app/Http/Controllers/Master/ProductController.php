<?php

namespace App\Http\Controllers\Master;

use App\Constants\FlashDataVariable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Product\StoreProductRequest;
use App\Http\Requests\Master\Product\UpdateProductRequest;
use App\Http\Resources\Master\ProductResource;
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
            ->with(['category', 'prices', 'images', 'inventoryItems']) // Only load essentials for index
            ->filters($request->only(['search', 'category', 'outlet', 'is_deleted']))
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Master/Product/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'category', 'outlet', 'is_deleted']),
            'categories' => \App\Models\Master\ProductCategory::currentBusiness()->get()->map(function ($row) {
                return [
                    'value' => $row->id,
                    'label' => $row->name,
                ];
            }),
            'rawCategories' => \App\Models\Master\ProductCategory::currentBusiness()->get(),
            'outlets' => \App\Models\Outlet::currentBusiness()->active()->get(),
            'modifierGroups' => \App\Models\Master\ModifierGroup::currentBusiness()->with('options')->get(),
            'inventoryItems' => \App\Models\Master\InventoryItem::currentBusiness()->get(),
            'baseProducts' => Product::currentBusiness()->where('product_type', '!=', 'bundle')->get(),
            'uoms' => \App\Models\Uom::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function show(Product $product)
    {
        // Load all detailed relationships for the PopUp form
        $product->load([
            'category', 'prices', 'outlets', 'inventoryItems.variantGroupOptions',
            'images', 'variantGroups.options', 'modifierGroups', 'bundleItems',
        ]);

        return new ProductResource($product);
    }

    public function create()
    {
        return Inertia::render('Master/Product/Form', [
            'categories' => \App\Models\Master\ProductCategory::currentBusiness()->get(),
            'outlets' => \App\Models\Outlet::currentBusiness()->active()->get(),
            'modifierGroups' => \App\Models\Master\ModifierGroup::currentBusiness()->with('options')->get(),
            'inventoryItems' => \App\Models\Master\InventoryItem::currentBusiness()->get(),
            'products' => Product::currentBusiness()->where('product_type', '!=', 'bundle')->get(), // for bundle components
            'uoms' => \App\Models\Uom::where('status', 'active')->get(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $data = $request->validated();
            $data['business_id'] = auth()->user()->business_id;

            $this->productService->createProduct($data);

            return redirect()->route('master.products.index')->with('success', 'Produk berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat produk: '.$e->getMessage());
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
            'product' => $product,
            'categories' => \App\Models\Master\ProductCategory::currentBusiness()->get(),
            'outlets' => \App\Models\Outlet::currentBusiness()->active()->get(),
            'modifierGroups' => \App\Models\Master\ModifierGroup::currentBusiness()->with('options')->get(),
            'inventoryItems' => \App\Models\Master\InventoryItem::currentBusiness()->get(),
            'products' => Product::currentBusiness()->where('product_type', '!=', 'bundle')->get(),
            'uoms' => \App\Models\Uom::where('status', 'active')->get(),
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
            return redirect()->back()->with('error', 'Gagal update produk: '.$e->getMessage());
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

    public function export(Request $request)
    {
        \App\Jobs\Master\ExportProductJob::dispatch(auth()->user(), auth()->user()->business_id, $request->all());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Ekspor CSV sedang diproses di latar belakang.'
        );
    }

    public function importTemplate()
    {
        $headers = [
            'SKU', 'Barcode', 'Nama Produk', 'Kategori', 'Deskripsi', 'Tipe Produk',
            'Nama Varian 1', 'Opsi Varian 1', 'Nama Varian 2', 'Opsi Varian 2',
            'Harga Dasar', 'Satuan', 'Lacak Stok', 'Minimum Stok', 'Status Tampil',
        ];

        $outlets = \App\Models\Outlet::currentBusiness()->active()->get();
        foreach ($outlets as $outlet) {
            $headers[] = 'Outlet: '.$outlet->name;
        }

        $dummy1 = ['PRD-001', 'Kopi Susu', 'Minuman', 'Kopi susu enak', 'basic', '', '', '', '', '15000', 'Cup', 'Ya', '10', 'Ya'];
        $dummy2 = ['PRD-002', 'T-Shirt', 'Pakaian', 'Kaos katun', 'basic', '', '', '', '', '50000', 'Pcs', 'Ya', '', 'Ya'];
        $dummy3 = ['PRD-002-S-M', '', '', '', '', 'Ukuran', 'S', 'Warna', 'Merah', '50000', '', '', '5', ''];
        $dummy4 = ['PRD-002-M-M', '', '', '', '', 'Ukuran', 'M', 'Warna', 'Merah', '55000', '', '', '5', ''];
        $dummy5 = ['PRD-002-L-B', '', '', '', '', 'Ukuran', 'L', 'Warna', 'Biru', '60000', '', '', '5', ''];

        foreach ($outlets as $outlet) {
            $dummy1[] = 'Ya';
            $dummy2[] = 'Ya';
            $dummy3[] = '';
            $dummy4[] = '';
            $dummy5[] = '';
        }

        return response()->stream(function () use ($headers, $dummy1, $dummy2, $dummy3, $dummy4, $dummy5) {
            $file = fopen('php://output', 'w');
            fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $headers);
            fputcsv($file, $dummy1);
            fputcsv($file, $dummy2);
            fputcsv($file, $dummy3);
            fputcsv($file, $dummy4);
            fputcsv($file, $dummy5);
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_produk.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:csv,txt|max:10240']);
        $path = $request->file('file')->store('imports', 'local');

        \App\Jobs\Master\ImportProductJob::dispatch(auth()->user(), $path, auth()->user()->business_id);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Proses impor CSV sedang berjalan di latar belakang.'
        );
    }
}
