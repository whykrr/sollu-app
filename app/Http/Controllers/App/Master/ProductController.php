<?php

namespace App\Http\Controllers\App\Master;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Master\Product\GetProductRequest;
use App\Http\Requests\App\Master\Product\StoreProductRequest;
use App\Http\Requests\App\Master\Product\UpdateProductRequest;
use App\Http\Resources\Master\ProductResource;
use App\Jobs\Master\ExportProductJob;
use App\Jobs\Master\ImportProductJob;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\ModifierGroup;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
use App\Models\Uom;
use App\Services\App\Master\ProductService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(GetProductRequest $request): Response
    {
        $this->authorize(PermissionEnum::PRODUCT_VIEW->value);

        $params = $request->validated();
        $outletId = $params['outlet'] ?? SelectedOutlet::make()->currentId();

        $products = Product::currentBusiness()
            ->with([
                'category:id,name',
                'prices' => function ($q) use ($outletId) {
                    $q->select('id', 'product_id', 'outlet_id', 'amount');
                    if ($outletId) {
                        $q->where(function ($sub) use ($outletId) {
                            $sub->where('outlet_id', $outletId)->orWhereNull('outlet_id');
                        });
                    }
                },
                'images:id,product_id,product_item_id,image_url,sort_order',
            ])
            ->filters($params)
            ->sortable($request->validated('sort', 'created_at'), $request->validated('direction', 'desc'))
            ->paginate($request->validated('perpage', 15))
            ->appends($request->query());

        return Inertia::render('Master/Product/Index', [
            'products' => $products,
            'params' => $params,
            'filters' => $params,
            'categories' => ProductCategory::currentBusiness()
                ->select('id', 'name')
                ->get()
                ->map(fn ($row) => [
                    'value' => $row->id,
                    'label' => $row->name,
                ]),
        ]);
    }

    public function formOptions(Request $request): JsonResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_VIEW->value);

        return response()->json([
            'categories' => ProductCategory::currentBusiness()->select('id', 'name')->get(),
            'outlets' => Outlet::currentBusiness()->active()->select('id', 'name')->get(),
            'modifierGroups' => ModifierGroup::currentBusiness()
                ->with('options:id,modifier_group_id,name,additional_price,is_default')
                ->select('id', 'name', 'selection_type', 'max_select', 'is_required')
                ->get(),
            'inventoryItems' => InventoryItem::currentBusiness()->get(),
            'baseProducts' => Product::currentBusiness()->where('product_type', '!=', 'bundle')->select('id', 'name', 'code')->get(),
            'uoms' => Uom::where('status', 'active')->orderBy('name')->select('id', 'name', 'code')->get(),
        ]);
    }

    public function show(Product $product): ProductResource
    {
        $this->authorize(PermissionEnum::PRODUCT_VIEW->value);

        if ($product->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        // Load all detailed relationships for the PopUp form
        $product->load([
            'category',
            'prices',
            'outlets',
            'productItems',
            'images',
            'variantGroups.options',
            'modifierGroups',
            'bundleItems',
        ]);

        return new ProductResource($product);
    }

    public function create(): Response
    {
        $this->authorize(PermissionEnum::PRODUCT_CREATE->value);

        return Inertia::render('Master/Product/Form', [
            'categories' => ProductCategory::currentBusiness()->get(),
            'outlets' => Outlet::currentBusiness()->active()->get(),
            'modifierGroups' => ModifierGroup::currentBusiness()->with('options')->get(),
            'inventoryItems' => InventoryItem::currentBusiness()->get(),
            'products' => Product::currentBusiness()->where('product_type', '!=', 'bundle')->get(),
            'uoms' => Uom::where('status', 'active')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_CREATE->value);

        try {
            $data = $request->validated();
            $data['business_id'] = auth()->user()->business_id;

            $this->productService->createProduct($data);

            return redirect()->route('master.products.index')->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::CREATE_SUCCESS
            );
        } catch (Exception $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                'Gagal membuat produk: '.$e->getMessage()
            );
        }
    }

    public function edit(Product $product): Response
    {
        $this->authorize(PermissionEnum::PRODUCT_UPDATE->value);

        if ($product->business_id !== auth()->user()->business_id) {
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
            'productItems',
            'images',
        ]);

        return Inertia::render('Master/Product/Form', [
            'product' => $product,
            'categories' => ProductCategory::currentBusiness()->get(),
            'outlets' => Outlet::currentBusiness()->active()->get(),
            'modifierGroups' => ModifierGroup::currentBusiness()->with('options')->get(),
            'inventoryItems' => InventoryItem::currentBusiness()->get(),
            'products' => Product::currentBusiness()->where('product_type', '!=', 'bundle')->get(),
            'uoms' => Uom::where('status', 'active')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_UPDATE->value);

        if ($product->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        try {
            $data = $request->validated();
            $this->productService->updateProduct($product, $data);

            return redirect()->route('master.products.index')->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::UPDATE_SUCCESS
            );
        } catch (Exception $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                'Gagal update produk: '.$e->getMessage()
            );
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_DELETE->value);

        if ($product->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        $product->delete();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    public function export(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_EXPORT->value);

        ExportProductJob::dispatch(auth()->user(), auth()->user()->business_id, $request->all());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Ekspor CSV sedang diproses di latar belakang.'
        );
    }

    public function importTemplate(): BinaryFileResponse
    {
        $headers = [
            'SKU', 'Barcode', 'Nama Produk', 'Kategori', 'Deskripsi', 'Tipe Produk',
            'Nama Varian 1', 'Opsi Varian 1', 'Nama Varian 2', 'Opsi Varian 2',
            'Harga Dasar', 'Satuan', 'Lacak Stok', 'Minimum Stok', 'Status Tampil',
        ];

        $outlets = Outlet::currentBusiness()->active()->get();
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

        $dummyData = [$dummy1, $dummy2, $dummy3, $dummy4, $dummy5];

        $export = new class($headers, $dummyData) implements FromArray, WithHeadings
        {
            public function __construct(
                private array $headers,
                private array $dummyData
            ) {}

            public function array(): array
            {
                return $this->dummyData;
            }

            public function headings(): array
            {
                return $this->headers;
            }
        };

        $filename = 'template_'.strtolower(class_basename($this)).'.xlsx';

        return Excel::download($export, $filename);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_IMPORT->value);

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        $path = $request->file('file')->store('imports', 'local');

        ImportProductJob::dispatch(auth()->user(), $path, auth()->user()->business_id);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Proses impor data sedang berjalan di latar belakang.'
        );
    }
}
