<?php

namespace App\Jobs\Master;

use App\Jobs\ImportExport\AbstractCsvExportJob;
use App\Models\Master\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ExportProductJob extends AbstractCsvExportJob
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

    protected function getQuery(): Builder
    {
        return Product::where('business_id', $this->businessId)
            ->with(['category', 'variantGroups.options', 'inventoryItems.uom', 'prices', 'outlets'])
            ->filters($this->filters)
            ->orderByDesc('created_at');
    }

    protected function getHeaders(): array
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

    protected function mapRow($row): array
    {
        return [];
    }

    public function handle(): void
    {
        $this->user = User::find($this->userId);

        if (! $this->user) {
            return;
        }

        Storage::disk('local')->makeDirectory('exports');

        $fileName = $this->getFileName();
        $filePath = 'exports/'.$fileName;

        $file = fopen('php://temp', 'w+');
        fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF));

        $outlets = \App\Models\Outlet::where('business_id', $this->businessId)->active()->get();
        $headers = $this->getHeaders();
        fputcsv($file, $headers);

        $this->getQuery()->chunk(500, function ($products) use ($file, $outlets) {
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
                    fputcsv($file, $parentRow);

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

                        fputcsv($file, $childRow);
                    }
                } else {
                    $inv = $product->inventoryItems->where('item_type', 'variant_sku')->first();
                    $parentRow[1] = $inv ? ($inv->barcode ?? '') : '';
                    $parentRow[13] = $inv ? $inv->min_stock : 0;
                    fputcsv($file, $parentRow);
                }
            }
        });

        rewind($file);
        $content = stream_get_contents($file);
        fclose($file);

        Storage::disk('local')->put($filePath, $content);
        $url = route('exports.download', ['file' => $fileName]);

        $expiresAt = now()->addDays(1);
        $this->user->notify(new \App\Notifications\CsvExportCompleted($this->getModuleName(), $fileName, $url, $expiresAt));
    }

    protected function getModuleName(): string
    {
        return 'Produk';
    }

    protected function getFileName(): string
    {
        return 'produk_export_'.time().'.csv';
    }
}
