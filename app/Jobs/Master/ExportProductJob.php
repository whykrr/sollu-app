<?php

namespace App\Jobs\Master;

use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\Master\Product;
use App\Models\User;
use App\Notifications\ExcelExportCompleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductJob extends AbstractExcelExportJob
{
    /**
     * @var array<string, mixed>
     */
    protected array $filters;

    public function __construct(
        $user,
        public string $businessId,
        array $filters = []
    ) {
        parent::__construct($user);
        $this->filters = $filters;
    }

    public function getQuery(): Builder
    {
        return Product::where('business_id', $this->businessId)
            ->with(['category', 'variantGroups.options', 'inventoryItems.uom', 'prices', 'outlets'])
            ->filters($this->filters)
            ->orderByDesc('created_at');
    }

    public function getHeaders(): array
    {
        $headers = [
            'SKU',
            'Barcode',
            'Nama Produk',
            'Kategori',
            'Deskripsi',
            'Tipe Produk',
            'Nama Varian 1',
            'Opsi Varian 1',
            'Nama Varian 2',
            'Opsi Varian 2',
            'Harga Dasar',
            'Satuan',
            'Lacak Stok',
            'Minimum Stok',
            'Status Tampil',
        ];

        $outlets = \App\Models\Outlet::where('business_id', $this->businessId)->active()->get();
        foreach ($outlets as $outlet) {
            $headers[] = 'Outlet: '.$outlet->name;
        }

        return $headers;
    }

    public function mapRow($row): array
    {
        return [];
    }

    public function handle(): void
    {
        $this->user = User::find($this->userId);

        if (! $this->user) {
            return;
        }

        Storage::makeDirectory('exports');

        $fileName = $this->getFileName();
        if (! str_ends_with($fileName, '.xlsx')) {
            $fileName = str_replace('.csv', '.xlsx', $fileName);
            if (! str_ends_with($fileName, '.xlsx')) {
                $fileName .= '.xlsx';
            }
        }
        $filePath = 'exports/'.$fileName;

        $outlets = \App\Models\Outlet::where('business_id', $this->businessId)->active()->get();
        $headers = $this->getHeaders();

        $rows = [$headers];

        $this->getQuery()->chunk(500, function ($products) use (&$rows, $outlets) {
            foreach ($products as $product) {
                $uomName = $product->inventoryItems->first()?->uom?->name ?? '';

                // Parent Row
                $parentRow = [
                    $product->code,
                    '', // Barcode default kosong (diisi di bawah jika tidak ada varian)
                    $product->name,
                    $product->category ? $product->category->name : '',
                    $product->description,
                    $product->product_type,
                    '', // Varian 1 Nama
                    '', // Varian 1 Opsi
                    '', // Varian 2 Nama
                    '', // Varian 2 Opsi
                    $product->prices->firstWhere('outlet_id', null)?->amount ?? 0,
                    $uomName,
                    $product->track_inventory ? 'Ya' : 'Tidak',
                    '', // Min Stok (Anak/Induk)
                    $product->is_show ? 'Ya' : 'Tidak',
                ];

                foreach ($outlets as $outlet) {
                    $hasOutlet = $product->outlets->contains('id', $outlet->id);
                    $parentRow[] = $hasOutlet ? 'Ya' : 'Tidak';
                }

                if ($product->product_type === 'basic' && $product->has_variant && $product->inventoryItems->where('item_type', 'variant_sku')->count() > 0) {
                    $variants = $product->inventoryItems->where('item_type', 'variant_sku');
                    $variantGroups = $product->variantGroups;

                    $parentRow[13] = ''; // Parent min stock is empty for variant products
                    $rows[] = $parentRow;

                    foreach ($variants as $variant) {
                        $v1Name = '';
                        $v1Opt = '';
                        $v2Name = '';
                        $v2Opt = '';

                        $opts = $variant->variantGroupOptions;
                        if ($opts->count() > 0) {
                            $vg1 = $variantGroups->firstWhere('id', $opts[0]->variant_group_id);
                            if ($vg1) {
                                $v1Name = $vg1->name;
                                $v1Opt = $opts[0]->name;
                            }
                        }
                        if ($opts->count() > 1) {
                            $vg2 = $variantGroups->firstWhere('id', $opts[1]->variant_group_id);
                            if ($vg2) {
                                $v2Name = $vg2->name;
                                $v2Opt = $opts[1]->name;
                            }
                        }

                        $childRow = [
                            $variant->sku,
                            $variant->barcode ?? '',
                            '', // Nama Produk Kosong
                            '',
                            '',
                            '',
                            $v1Name,
                            $v1Opt,
                            $v2Name,
                            $v2Opt,
                            $product->prices->where('inventory_item_id', $variant->id)->whereNull('outlet_id')->first()?->amount ?? $parentRow[10],
                            '',
                            '',
                            $variant->min_stock,
                            '',
                        ];

                        foreach ($outlets as $outlet) {
                            $childRow[] = '';
                        }

                        $rows[] = $childRow;
                    }
                } else {
                    $inv = $product->inventoryItems->where('item_type', 'variant_sku')->first();
                    $parentRow[1] = $inv ? ($inv->barcode ?? '') : '';
                    $parentRow[13] = $inv ? $inv->min_stock : 0;
                    $rows[] = $parentRow;
                }
            }
        });

        $export = new class($rows) implements FromArray
        {
            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }
        };

        Excel::store($export, $filePath, 'public', \Maatwebsite\Excel\Excel::XLSX);
        $url = route('exports.download', ['file' => $fileName]);

        $expiresAt = now()->addDays(1);
        $this->user->notify(new ExcelExportCompleted($this->getModuleName(), $fileName, $url, $expiresAt));
    }

    public function getModuleName(): string
    {
        return 'Produk';
    }

    public function getFileName(): string
    {
        return 'produk_export_'.time().'.xlsx';
    }
}
