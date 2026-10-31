<?php

namespace App\Http\Controllers\App\Master;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Master\Service\GetServiceProductRequest;
use App\Http\Requests\App\Master\Service\StoreServiceProductRequest;
use App\Http\Requests\App\Master\Service\UpdateServiceProductRequest;
use App\Http\Resources\Master\ProductResource;
use App\Jobs\Master\ExportProductJob;
use App\Jobs\Master\ImportProductJob;
use App\Models\Business;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
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

class ServiceProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(GetServiceProductRequest $request): Response
    {
        $params = $request->validated();
        $outletId = SelectedOutlet::resolveEffectiveOutletId($request->user(), $params['outlet'] ?? $params['outlet_id'] ?? null);
        $params['outlet'] = $outletId;

        $services = Product::currentBusiness()
            ->services()
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

        return Inertia::render('Master/Service/Index', [
            'services' => $services,
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
        ]);
    }

    public function show(Product $service): ProductResource
    {
        $this->authorize(PermissionEnum::PRODUCT_VIEW->value);

        if ($service->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        if (! $service->isService()) {
            abort(404);
        }

        $service->load([
            'category:id,name',
            'prices:id,product_id,product_item_id,outlet_id,amount',
            'outlets:id,name',
            'images:id,product_id,product_item_id,image_url,sort_order',
        ]);

        return new ProductResource($service);
    }

    public function store(StoreServiceProductRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['business_id'] = auth()->user()->business_id;

            $this->productService->createService($data);

            return redirect()->back(fallback: route('master.services.index'))->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::CREATE_SUCCESS
            );
        } catch (Exception $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                'Gagal menambahkan layanan: '.$e->getMessage()
            );
        }
    }

    public function update(UpdateServiceProductRequest $request, Product $service): RedirectResponse
    {
        if ($service->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        if (! $service->isService()) {
            abort(404);
        }

        try {
            $data = $request->validated();
            $this->productService->updateService($service, $data);

            return redirect()->back(fallback: route('master.services.index'))->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::UPDATE_SUCCESS
            );
        } catch (Exception $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                'Gagal memperbarui layanan: '.$e->getMessage()
            );
        }
    }

    public function destroy(Product $service): RedirectResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_DELETE->value);

        if ($service->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        if (! $service->isService()) {
            abort(404);
        }

        $this->productService->deleteService($service);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    public function export(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::PRODUCT_VIEW->value);

        $filters = array_merge($request->all(), ['product_type' => 'service']);
        ExportProductJob::dispatch(auth()->user(), auth()->user()->business_id, $filters);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::EXPORT_PROCESSING
        );
    }

    public function importTemplate(): BinaryFileResponse
    {
        $business = Business::findCached(auth()->user()->business_id);
        $hasMultiOutlet = $business ? $business->hasFeature(FeatureEnum::MULTI_OUTLET) : true;

        $headers = [
            'Kode Layanan', 'Barcode', 'Nama Layanan', 'Kategori', 'Deskripsi', 'Tarif Dasar', 'Status Tampil',
        ];

        $outlets = Outlet::currentBusiness()->active()->get();
        if ($hasMultiOutlet && $outlets->count() > 1) {
            foreach ($outlets as $outlet) {
                $headers[] = 'Outlet: '.$outlet->name;
            }
        }

        $dummy1 = ['SRV-001', '899888777001', 'Potong Rambut Pria', 'Grooming', 'Layanan cuci dan potong rambut pria standar', '35000', 'Ya'];
        $dummy2 = ['SRV-002', '899888777002', 'Perawatan Wajah (Facial)', 'Treatment', 'Treatment relaksasi dan pembersihan wajah', '120000', 'Ya'];

        if ($hasMultiOutlet && $outlets->count() > 1) {
            foreach ($outlets as $outlet) {
                $dummy1[] = 'Ya';
                $dummy2[] = 'Ya';
            }
        }

        $dummyData = [$dummy1, $dummy2];

        $instructions = [
            ['KOLOM', 'TIPE / WAJIB', 'DESKRIPSI & KETENTUAN STANDAR'],
            ['Kode Layanan', 'Teks (Opsional)', 'Kode unik layanan (SKU). Harus unik di seluruh merchant dan tidak boleh sama dengan Barcode manapun.'],
            ['Barcode', 'Teks (Opsional)', 'Kode barcode unik (misal tiket/voucher). Harus unik di seluruh merchant dan tidak boleh sama dengan Kode SKU manapun.'],
            ['Nama Layanan', 'Teks (Wajib)', 'Nama produk layanan / jasa.'],
            ['Kategori', 'Teks (Opsional)', 'Nama kategori layanan. Jika belum terdaftar, sistem akan otomatis menambahkannya.'],
            ['Deskripsi', 'Teks (Opsional)', 'Keterangan detail rincian pengerjaan layanan.'],
            ['Tarif Dasar', 'Angka (Wajib)', 'Tarif layanan dalam format angka tanpa titik/koma (contoh: 35000).'],
            ['Status Tampil', 'Teks (Ya / Tidak)', 'Ketik Ya agar layanan muncul di katalog transaksi kasir, atau Tidak untuk menyembunyikannya.'],
        ];

        if ($hasMultiOutlet && $outlets->count() > 1) {
            $instructions[] = ['Outlet: [Nama Outlet]', 'Teks (Ya / Tidak)', 'Ketik Ya jika layanan tersedia untuk ditransaksikan di outlet tersebut.'];
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
                            return 'Template Impor Layanan';
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

        $filename = 'template_produk_layanan.xlsx';

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
            ResourceMessage::IMPORT_PROCESSING
        );
    }
}
