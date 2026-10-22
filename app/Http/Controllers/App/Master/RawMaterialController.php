<?php

namespace App\Http\Controllers\App\Master;

use App\Constants\FlashDataVariable;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Inventory\RawMaterial\IndexRawMaterialRequest;
use App\Http\Requests\App\Inventory\RawMaterial\StoreRawMaterialRequest;
use App\Http\Requests\App\Inventory\RawMaterial\UpdateRawMaterialRequest;
use App\Jobs\Inventory\ExportRawMaterialJob;
use App\Jobs\Inventory\ImportRawMaterialJob;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\ProductRecipeItem;
use App\Models\Uom;
use App\Services\App\Inventory\RawMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class RawMaterialController extends Controller
{
    public function __construct(
        protected RawMaterialService $rawMaterialService
    ) {}

    public function index(IndexRawMaterialRequest $request): Response
    {
        $businessId = $request->user()->business_id;
        $validated = $request->validated();

        $rawMaterials = InventoryItem::query()
            ->where('inventory_items.business_id', $businessId)
            ->joinProductItem()
            ->where('product_items.item_type', 'raw_material')
            ->with(['uom:id,name', 'balances'])
            ->filters($validated)
            ->when($request->validated('sort'), function ($query, $sort) use ($request) {
                if ($sort === 'sku' || $sort === 'barcode') {
                    $query->orderBy("product_items.{$sort}", $request->validated('direction') ?? 'asc');
                } else {
                    $query->orderBy("inventory_items.{$sort}", $request->validated('direction') ?? 'asc');
                }
            }, function ($query) {
                $query->latest('inventory_items.created_at');
            })
            ->paginate($request->validated('per_page') ?? 15)
            ->withQueryString();

        $uoms = Uom::select('id', 'name')->where('status', 'active')->get();

        return Inertia::render('Master/Product/RawMaterial/Index', [
            'rawMaterials' => $rawMaterials,
            'uoms' => $uoms,
            'filters' => $request->only(['search', 'track_inventory', 'sort', 'direction']),
        ]);
    }

    public function store(StoreRawMaterialRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // Mandatory invariants for raw materials
        $data['item_type'] = 'raw_material';
        $data['sellable'] = false;
        $data['is_show'] = false;
        $data['track_inventory'] = true;

        $this->rawMaterialService->createRawMaterial($data, $request->user()->business);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Bahan baku berhasil ditambahkan.'
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $item = InventoryItem::currentBusiness()
            ->with(['uom', 'balances', 'productItem'])
            ->findOrFail($id);

        return response()->json($item);
    }

    public function update(UpdateRawMaterialRequest $request, string $id): RedirectResponse
    {
        $item = InventoryItem::currentBusiness()->findOrFail($id);
        $data = $request->validated();

        // Ensure invariants remain enforced
        $data['item_type'] = 'raw_material';
        $data['sellable'] = false;
        $data['is_show'] = false;
        $data['track_inventory'] = true;

        $this->rawMaterialService->updateRawMaterial($item, $data);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Bahan baku berhasil diperbarui.'
        );
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $item = InventoryItem::currentBusiness()->findOrFail($id);

        // Check if raw material is referenced in any BOM recipes
        $isUsedInRecipe = ProductRecipeItem::where('product_item_id', $item->product_item_id)->exists();
        if ($isUsedInRecipe) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                'Bahan baku tidak dapat dihapus karena sedang digunakan dalam resep aktif.'
            );
        }

        $hasMovements = $item->movements()->exists();
        $hasStock = $item->balances()->where('current_stock', '>', 0)->exists();

        if ($hasMovements || $hasStock) {
            $item->update(['is_active' => false]);
            if ($item->productItem) {
                $item->productItem->update(['is_active' => false]);
            }

            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                'Bahan baku tidak dapat dihapus karena memiliki riwayat stok. Status telah dinonaktifkan.'
            );
        }

        if ($item->productItem) {
            $item->productItem->delete();
        }
        $item->delete();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Bahan baku berhasil dihapus secara permanen.'
        );
    }

    public function export(IndexRawMaterialRequest $request): RedirectResponse
    {
        ExportRawMaterialJob::dispatch(
            Auth::user(),
            Auth::user()->business_id,
            $request->validated()
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Ekspor sedang diproses di latar belakang.'
        );
    }

    public function importTemplate()
    {
        $headers = ['Nama', 'SKU', 'Barcode', 'Satuan', 'Minimum Stok', 'Status Aktif'];
        $dummy = ['Gula Pasir', 'RM-GULA-01', '8991234567890', 'Kilogram', '10', 'Ya'];

        $export = new class([$dummy], $headers) implements FromArray, WithHeadings
        {
            public function __construct(private array $data, private array $headers) {}

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headers;
            }
        };

        return Excel::download($export, 'template_bahan_baku.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
        $path = $request->file('file')->store('imports', 'local');

        ImportRawMaterialJob::dispatch(Auth::user(), $path, Auth::user()->business_id);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'File berhasil diunggah. Proses impor sedang berjalan di latar belakang.'
        );
    }
}
