<?php

namespace App\Http\Controllers\App\Master;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Master\Product\GetProductRequest;
use App\Http\Requests\App\Master\Product\StoreProductRequest;
use App\Http\Requests\App\Master\Product\UpdateProductRequest;
use App\Http\Resources\Master\ProductResource;
use App\Jobs\Master\ExportProductJob;
use App\Jobs\Master\ImportProductJob;
use App\Models\Business;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\ModifierGroup;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
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
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(GetProductRequest $request): Response
    {
        $params = $request->validated();
        $outletId = SelectedOutlet::resolveEffectiveOutletId($request->user(), $params['outlet'] ?? $params['outlet_id'] ?? null);
        $params['outlet'] = $outletId;

        $products = Product::currentBusiness()
            ->goods()
            ->select([
                'id',
                'business_id',
                'product_category_id',
                'product_type',
                'name',
                'code',
                'image_url',
                'is_show',
                'sellable',
                'has_variant',
                'created_at',
            ])
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
            'categories' => ProductCategory::currentBusiness()->select('id', 'name')->orderBy('name')->get(),
            'outlets' => Outlet::currentBusiness()->active()->select('id', 'name')->orderBy('name')->get(),
            'modifierGroups' => ModifierGroup::currentBusiness()
                ->with('options:id,modifier_group_id,name,additional_price,is_default')
                ->select('id', 'name', 'selection_type', 'max_select', 'is_required')
                ->get(),
            'inventoryItems' => [],
            'baseProducts' => [],
            'uoms' => Uom::where('status', 'active')->orderBy('name')->select('id', 'name', 'code')->get(),
            'rawMaterials' => ProductItem::currentBusiness()
                ->where('item_type', 'raw_material')
                ->where('is_active', true)
                ->select(['id', 'product_id', 'business_id', 'name', 'uom_id'])
                ->with([
                    'uom:id,name',
                    'inventoryItem' => function ($q) {
                        $q->select(['id', 'product_item_id', 'business_id'])
                            ->with('balances:id,inventory_item_id,outlet_id,average_cost');
                    },
                ])
                ->get(),
        ]);
    }

    public function show(Product $product): ProductResource
    {
        $this->authorize(PermissionEnum::PRODUCT_VIEW->value);

        if ($product->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        if (! $product->isBasic()) {
            abort(404);
        }

        // Load all detailed relationships for the PopUp form
        $product->load([
            'category:id,name',
            'prices:id,product_id,product_item_id,outlet_id,amount',
            'outlets:id,name',
            'productItems' => function ($q) {
                $q->select([
                    'id',
                    'business_id',
                    'product_id',
                    'uom_id',
                    'item_type',
                    'name',
                    'variant_combination',
                    'sku',
                    'barcode',
                    'track_inventory',
                    'is_show',
                    'sellable',
                    'is_active',
                ])->with([
                    'uom:id,name',
                    'inventoryItem:id,product_item_id,business_id,minimum_stock',
                ]);
            },
            'images:id,product_id,product_item_id,image_url,sort_order',
            'variantGroups' => function ($q) {
                $q->select(['id', 'product_id', 'name', 'sort_order'])
                    ->with('options:id,variant_group_id,name,sort_order');
            },
            'activeRecipe.items.productItem.uom:id,name',
            'modifierGroups:id,name',
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
        try {
            $data = $request->validated();
            $data['business_id'] = auth()->user()->business_id;

            $this->productService->createProduct($data);

            return redirect()->back(fallback: route('master.products.index'))->with(
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

        if (! $product->isBasic()) {
            abort(404);
        }

        $product->load([
            'category',
            'variantGroups.options',
            'modifierGroups',
            'recipeVersions.items',
            'bundleItems',
            'prices',
            'outlets',
            'productItems.inventoryItem.balances',
            'productItems.inventoryItem.uom',
            'inventoryItems.balances',
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
        if ($product->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        if (! $product->isBasic()) {
            abort(404);
        }

        try {
            $data = $request->validated();
            $this->productService->updateProduct($product, $data);

            return redirect()->back(fallback: route('master.products.index'))->with(
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

        if (! $product->isBasic()) {
            abort(404);
        }

        $this->productService->deleteProduct($product);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    public function export(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_VIEW->value);

        $filters = array_merge($request->all(), ['product_type' => 'basic']);
        ExportProductJob::dispatch(auth()->user(), auth()->user()->business_id, $filters);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Ekspor CSV sedang diproses di latar belakang.'
        );
    }

    public function importTemplate(): BinaryFileResponse
    {
        $user = auth()->user();
        $business = $user->relationLoaded('business') ? $user->business : ($user->business_id ? Business::findCached($user->business_id) : null);

        $hasVariant = $business ? $business->hasFeature(FeatureEnum::PRODUCT_VARIANT) : true;
        $hasMultiOutlet = $business ? $business->hasFeature(FeatureEnum::MULTI_OUTLET) : true;

        $headers = [
            'SKU', 'Barcode', 'Nama Produk', 'Kategori', 'Deskripsi', 'Tipe Produk',
        ];

        if ($hasVariant) {
            $headers = array_merge($headers, [
                'Nama Varian 1', 'Opsi Varian 1', 'Nama Varian 2', 'Opsi Varian 2',
            ]);
        }

        $headers = array_merge($headers, [
            'Harga Dasar', 'Satuan', 'Lacak Stok', 'Minimum Stok', 'Status Tampil',
        ]);

        $outlets = Outlet::currentBusiness()->active()->get();
        if ($hasMultiOutlet && $outlets->count() > 1) {
            foreach ($outlets as $outlet) {
                $headers[] = 'Outlet: '.$outlet->name;
            }
        }

        $dummyData = [];
        if ($hasVariant) {
            $d1 = ['PRD-001', '899123456001', 'Kopi Susu', 'Minuman', 'Kopi susu gula aren', 'basic', '', '', '', '', '15000', 'Cup', 'Ya', '10', 'Ya'];
            $d2 = ['PRD-002', '', 'Kaos Polos', 'Pakaian', 'Kaos katun premium', 'basic', '', '', '', '', '50000', 'Pcs', 'Ya', '', 'Ya'];
            $d3 = ['PRD-002-S-M', '899123456002', '', '', '', '', 'Ukuran', 'S', 'Warna', 'Merah', '50000', '', '', '5', ''];
            $d4 = ['PRD-002-M-M', '899123456003', '', '', '', '', 'Ukuran', 'M', 'Warna', 'Merah', '55000', '', '', '5', ''];
            $d5 = ['PRD-002-L-B', '899123456004', '', '', '', '', 'Ukuran', 'L', 'Warna', 'Biru', '60000', '', '', '5', ''];

            if ($hasMultiOutlet && $outlets->count() > 1) {
                foreach ($outlets as $outlet) {
                    $d1[] = 'Ya';
                    $d2[] = 'Ya';
                    $d3[] = '';
                    $d4[] = '';
                    $d5[] = '';
                }
            }
            $dummyData = [$d1, $d2, $d3, $d4, $d5];
        } else {
            $d1 = ['PRD-001', '899123456001', 'Kopi Susu', 'Minuman', 'Kopi susu gula aren', 'basic', '15000', 'Cup', 'Ya', '10', 'Ya'];
            $d2 = ['PRD-002', '899123456002', 'Roti Bakar', 'Makanan', 'Roti bakar coklat', 'basic', '20000', 'Pcs', 'Ya', '5', 'Ya'];
            if ($hasMultiOutlet && $outlets->count() > 1) {
                foreach ($outlets as $outlet) {
                    $d1[] = 'Ya';
                    $d2[] = 'Ya';
                }
            }
            $dummyData = [$d1, $d2];
        }

        $instructions = [
            ['KOLOM', 'TIPE / WAJIB', 'DESKRIPSI & KETENTUAN STANDAR'],
            ['SKU', 'Teks (Opsional)', 'Kode unik produk. Harus unik di seluruh merchant dan tidak boleh sama dengan Barcode manapun.'],
            ['Barcode', 'Teks (Opsional)', 'Kode barcode unik. Harus unik di seluruh merchant dan tidak boleh sama dengan SKU manapun.'],
            ['Nama Produk', 'Teks (Wajib utk Induk)', 'Nama barang produk. Pada baris varian anak, kosongkan kolom ini.'],
            ['Kategori', 'Teks (Opsional)', 'Nama kategori produk. Jika belum terdaftar, sistem akan otomatis menambahkannya.'],
            ['Deskripsi', 'Teks (Opsional)', 'Keterangan detail atau deskripsi produk.'],
            ['Tipe Produk', 'Teks (Opsional)', 'Pilihan: basic (Barang Fisik), service (Layanan). Default: basic.'],
        ];

        if ($hasVariant) {
            $instructions[] = ['Nama Varian 1', 'Teks (Khusus Varian)', 'Nama grup varian 1 (contoh: Ukuran, Rasa).'];
            $instructions[] = ['Opsi Varian 1', 'Teks (Khusus Varian)', 'Nilai opsi varian 1 (contoh: S, M, L).'];
            $instructions[] = ['Nama Varian 2', 'Teks (Khusus Varian)', 'Nama grup varian 2 (contoh: Warna, Level).'];
            $instructions[] = ['Opsi Varian 2', 'Teks (Khusus Varian)', 'Nilai opsi varian 2 (contoh: Merah, Pedas).'];
        }

        $instructions[] = ['Harga Dasar', 'Angka (Wajib)', 'Harga jual produk dalam format angka tanpa titik/koma (contoh: 15000).'];
        $instructions[] = ['Satuan', 'Teks (Opsional)', 'Satuan produk (contoh: Pcs, Cup, Botol). Harus terdaftar pada menu Satuan master.'];
        $instructions[] = ['Lacak Stok', 'Teks (Ya / Tidak)', 'Ketik Ya untuk mengaktifkan pelacakan inventori, atau Tidak.'];
        $instructions[] = ['Minimum Stok', 'Angka (Opsional)', 'Batas minimum stok untuk memicu peringatan persediaan menipis.'];
        $instructions[] = ['Status Tampil', 'Teks (Ya / Tidak)', 'Ketik Ya agar produk muncul di katalog kasir, atau Tidak untuk menyembunyikannya.'];

        if ($hasMultiOutlet && $outlets->count() > 1) {
            $instructions[] = ['Outlet: [Nama Outlet]', 'Teks (Ya / Tidak)', 'Ketik Ya jika produk tersedia untuk dijual di outlet tersebut.'];
        }

        $export = new class($headers, $dummyData, $instructions) implements WithMultipleSheets
        {
            public function __construct(
                private array $headers,
                private array $dummyData,
                private array $instructions
            ) {}

            public function sheets(): array
            {
                return [
                    new class($this->headers, $this->dummyData) implements FromArray, WithHeadings, WithTitle
                    {
                        public function __construct(private array $headers, private array $data) {}

                        public function array(): array
                        {
                            return $this->data;
                        }

                        public function headings(): array
                        {
                            return $this->headers;
                        }

                        public function title(): string
                        {
                            return 'Template Impor Produk';
                        }
                    },
                    new class($this->instructions) implements FromArray, WithTitle
                    {
                        public function __construct(private array $instructions) {}

                        public function array(): array
                        {
                            return $this->instructions;
                        }

                        public function title(): string
                        {
                            return 'Petunjuk & Kamus Data';
                        }
                    },
                ];
            }
        };

        $filename = 'template_impor_produk.xlsx';

        return Excel::download($export, $filename);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_CREATE->value);

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        $path = $request->file('file')->store('imports', 'local');

        ImportProductJob::dispatch(auth()->user(), $path, auth()->user()->business_id);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Proses impor data sedang berjalan di latar belakang.'
        );
    }
}
