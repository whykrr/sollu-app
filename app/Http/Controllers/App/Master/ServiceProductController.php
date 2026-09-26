<?php

namespace App\Http\Controllers\App\Master;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Master\Service\GetServiceProductRequest;
use App\Http\Requests\App\Master\Service\StoreServiceProductRequest;
use App\Http\Requests\App\Master\Service\UpdateServiceProductRequest;
use App\Http\Resources\Master\ProductResource;
use App\Jobs\Master\ExportProductJob;
use App\Jobs\Master\ImportProductJob;
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
        $this->authorize(PermissionEnum::SERVICE_VIEW->value);

        return response()->json([
            'categories' => ProductCategory::currentBusiness()->select('id', 'name')->get(),
            'outlets' => Outlet::currentBusiness()->active()->select('id', 'name')->get(),
        ]);
    }

    public function show(Product $service): ProductResource
    {
        $this->authorize(PermissionEnum::SERVICE_VIEW->value);

        if ($service->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        if (! $service->isService()) {
            abort(404);
        }

        $service->load([
            'category',
            'prices',
            'outlets',
            'images',
        ]);

        return new ProductResource($service);
    }

    public function store(StoreServiceProductRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['business_id'] = auth()->user()->business_id;

            $this->productService->createService($data);

            return redirect()->route('master.services.index')->with(
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

            return redirect()->route('master.services.index')->with(
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
        $this->authorize(PermissionEnum::SERVICE_DELETE->value);

        if ($service->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        if (! $service->isService()) {
            abort(404);
        }

        $service->delete();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    public function export(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::SERVICE_EXPORT->value);

        $filters = array_merge($request->all(), ['product_type' => 'service']);
        ExportProductJob::dispatch(auth()->user(), auth()->user()->business_id, $filters);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::EXPORT_PROCESSING
        );
    }

    public function importTemplate(): BinaryFileResponse
    {
        $headers = [
            'Kode Layanan', 'Nama Layanan', 'Kategori', 'Deskripsi', 'Tarif Dasar', 'Status Tampil',
        ];

        $outlets = Outlet::currentBusiness()->active()->get();
        foreach ($outlets as $outlet) {
            $headers[] = 'Outlet: '.$outlet->name;
        }

        $dummy1 = ['SRV-001', 'Potong Rambut Pria', 'Grooming', 'Layanan cuci dan potong rambut pria standar', '35000', 'Ya'];
        $dummy2 = ['SRV-002', 'Perawatan Wajah (Facial)', 'Treatment', 'Treatment relaksasi dan pembersihan wajah', '120000', 'Ya'];

        foreach ($outlets as $outlet) {
            $dummy1[] = 'Ya';
            $dummy2[] = 'Ya';
        }

        $dummyData = [$dummy1, $dummy2];

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

        $filename = 'template_produk_layanan.xlsx';

        return Excel::download($export, $filename);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::SERVICE_IMPORT->value);

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        $path = $request->file('file')->store('imports', 'local');

        ImportProductJob::dispatch(auth()->user(), $path, auth()->user()->business_id);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::IMPORT_PROCESSING
        );
    }
}
