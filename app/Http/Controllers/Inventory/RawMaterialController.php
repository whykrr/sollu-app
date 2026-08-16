<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\RawMaterial\IndexRawMaterialRequest;
use App\Http\Requests\Inventory\RawMaterial\StoreRawMaterialRequest;
use App\Http\Requests\Inventory\RawMaterial\UpdateRawMaterialRequest;
use App\Jobs\Inventory\ExportRawMaterialJob;
use App\Jobs\Inventory\ImportRawMaterialJob;
use App\Models\Inventory\InventoryItem;
use App\Models\Uom;
use App\Services\Inventory\RawMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;
use Inertia\ResponseFactory;

class RawMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexRawMaterialRequest $request): ResponseFactory|Response
    {
        $uoms = Uom::select('id', 'name')->get();

        $validated = $request->validated();

        $rawMaterials = InventoryItem::currentBusiness()
            ->where('item_type', 'raw_material')
            ->with('uom:id,name')
            ->filters($validated)
            ->when($request->validated('sort'), function ($query, $sort) use ($request) {
                $query->orderBy($sort, $request->validated('direction') ?? 'asc');
            }, function ($query) {
                $query->latest();
            })
            ->paginate($request->validated('per_page') ?? 20)
            ->withQueryString();

        return inertia('Inventory/RawMaterial/Index', [
            'rawMaterials' => $rawMaterials,
            'uoms' => $uoms,
            'filters' => $request->only(['search', 'track_inventory', 'sort', 'direction']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRawMaterialRequest $request, RawMaterialService $service)
    {
        $service->createRawMaterial($request->validated(), Auth::user()->business);

        return redirect()->back()->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRawMaterialRequest $request, string $id, RawMaterialService $service)
    {
        $item = InventoryItem::currentBusiness()->findOrFail($id);

        $service->updateRawMaterial($item, $request->validated());

        return redirect()->back()->with('success', 'Bahan baku berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = InventoryItem::currentBusiness()->findOrFail($id);

        $hasMovements = $item->movements()->exists();
        $hasStock = $item->balances()->where('current_stock', '>', 0)->exists();

        if ($hasMovements || $hasStock) {
            $item->update(['is_active' => false]);

            return redirect()->back()->with('error', 'Bahan baku tidak dapat dihapus karena memiliki riwayat stok. Status telah dinonaktifkan.');
        }

        $item->delete();

        return redirect()->back()->with('success', 'Bahan baku berhasil dihapus secara permanen.');
    }

    /**
     * Export the resource to CSV in the background.
     */
    public function export(IndexRawMaterialRequest $request)
    {
        ExportRawMaterialJob::dispatch(
            Auth::user(),
            Auth::user()->business_id,
            $request->validated()
        );

        return redirect()->back()->with('success', 'Ekspor sedang diproses. Anda akan mendapatkan notifikasi jika sudah selesai.');
    }

    /**
     * Download the CSV import template.
     */
    public function importTemplate()
    {
        $headers = [
            'Nama',
            'SKU',
            'Barcode',
            'Satuan',
            'Minimum Stok',
            'Lacak Inventori',
            'Status Aktif',
        ];

        $dummyData = [
            'Gula Pasir',
            'GL-001',
            '8991234567890',
            'Kilogram',
            '10',
            'Ya',
            'Ya',
        ];

        $callback = function () use ($headers, $dummyData) {
            $file = fopen('php://output', 'w');
            fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF)); // Write BOM
            fputcsv($file, $headers);
            fputcsv($file, $dummyData);
            fclose($file);
        };

        $export = new class($headers, $dummyData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings
        {
            private $headers;

            private $dummyData;

            public function __construct($headers, $dummyData)
            {
                $this->headers = $headers;
                $this->dummyData = $dummyData;
            }

            public function array(): array
            {
                return [$this->dummyData];
            }

            public function headings(): array
            {
                return $this->headers;
            }
        };

        $filename = 'template_'.strtolower(class_basename($this)).'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    /**
     * Handle the CSV upload and dispatch the import job.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        ImportRawMaterialJob::dispatch(
            Auth::user(),
            $path,
            Auth::user()->business_id
        );

        return redirect()->back()->with('success', 'File CSV berhasil diunggah. Proses impor sedang berjalan di latar belakang.');
    }
}
