<?php

namespace App\Jobs\Master;

use App\Enums\FeatureEnum;
use App\Enums\ProductTypeEnum;
use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\Business;
use App\Models\Master\Product;
use App\Models\Outlet;
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
            ->select([
                'id',
                'business_id',
                'product_category_id',
                'product_type',
                'has_variant',
                'track_inventory',
                'code',
                'name',
                'description',
                'is_show',
                'sellable',
                'created_at',
            ])
            ->with([
                'category:id,name',
                'variantGroups:id,product_id,name',
                'variantGroups.options:id,variant_group_id,name,sort_order',
                'productItems:id,product_id,item_type,sku,barcode,name,uom_id',
                'productItems.variantGroupOptions:id,name,variant_group_id',
                'productItems.uom:id,name',
                'prices:id,product_id,product_item_id,outlet_id,amount',
                'outlets:id,name',
            ])
            ->filters($this->filters)
            ->orderByDesc('created_at');
    }

    public function getHeaders(): array
    {
        $business = Business::findCached($this->businessId);
        $hasVariant = $business ? $business->hasFeature(FeatureEnum::PRODUCT_VARIANT) : true;
        $hasMultiOutlet = $business ? $business->hasFeature(FeatureEnum::MULTI_OUTLET) : true;

        $headers = [
            'SKU',
            'Barcode',
            'Nama Produk',
            'Kategori',
            'Deskripsi',
            'Tipe Produk',
        ];

        if ($hasVariant) {
            $headers = array_merge($headers, [
                'Nama Varian 1',
                'Opsi Varian 1',
                'Nama Varian 2',
                'Opsi Varian 2',
            ]);
        }

        $headers = array_merge($headers, [
            'Harga Dasar',
            'Satuan',
            'Lacak Stok',
            'Minimum Stok',
            'Status Tampil',
        ]);

        $outlets = Outlet::where('business_id', $this->businessId)->active()->get();
        if ($hasMultiOutlet && $outlets->count() > 1) {
            foreach ($outlets as $outlet) {
                $headers[] = 'Outlet: '.$outlet->name;
            }
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

        $business = Business::findCached($this->businessId);
        $hasVariant = $business ? $business->hasFeature(FeatureEnum::PRODUCT_VARIANT) : true;
        $hasMultiOutlet = $business ? $business->hasFeature(FeatureEnum::MULTI_OUTLET) : true;

        $outlets = Outlet::where('business_id', $this->businessId)->active()->get();
        $headers = $this->getHeaders();

        $rows = [$headers];

        $this->getQuery()->chunk(200, function ($products) use (&$rows, $outlets, $hasVariant, $hasMultiOutlet) {
            foreach ($products as $product) {
                $firstItem = $product->productItems->first();
                $uomName = $firstItem?->uom?->name ?? '';

                $basePrice = $product->prices->firstWhere('outlet_id', null)?->amount ?? 0;
                $trackInventory = $product->track_inventory ? 'Ya' : 'Tidak';
                $isShow = $product->is_show ? 'Ya' : 'Tidak';

                $variantItems = $product->productItems->where('item_type', 'variant_sku');
                $isVariantProduct = $hasVariant && $product->isBasic() && $product->has_variant && $variantItems->count() > 0;

                // Parent Row
                $parentRow = [
                    $product->code,
                    $isVariantProduct ? '' : ($firstItem?->barcode ?? ''),
                    $product->name,
                    $product->category ? $product->category->name : '',
                    $product->description,
                    $product->product_type instanceof ProductTypeEnum ? $product->product_type->value : (string) $product->product_type,
                ];

                if ($hasVariant) {
                    $parentRow[] = ''; // Varian 1 Nama
                    $parentRow[] = ''; // Varian 1 Opsi
                    $parentRow[] = ''; // Varian 2 Nama
                    $parentRow[] = ''; // Varian 2 Opsi
                }

                $parentRow[] = $basePrice;
                $parentRow[] = $uomName;
                $parentRow[] = $trackInventory;
                $parentRow[] = $isVariantProduct ? '' : ($firstItem?->minimum_stock ?? '');
                $parentRow[] = $isShow;

                if ($hasMultiOutlet && $outlets->count() > 1) {
                    foreach ($outlets as $outlet) {
                        $hasOutlet = $product->outlets->contains('id', $outlet->id);
                        $parentRow[] = $hasOutlet ? 'Ya' : 'Tidak';
                    }
                }

                $rows[] = $parentRow;

                if ($isVariantProduct) {
                    $variantGroups = $product->variantGroups;

                    foreach ($variantItems as $variant) {
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

                        $childPrice = $product->prices->where('product_item_id', $variant->id)->whereNull('outlet_id')->first()?->amount ?? $basePrice;

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
                            $childPrice,
                            '',
                            '',
                            $variant->minimum_stock ?? '',
                            '',
                        ];

                        if ($hasMultiOutlet && $outlets->count() > 1) {
                            foreach ($outlets as $outlet) {
                                $childRow[] = '';
                            }
                        }

                        $rows[] = $childRow;
                    }
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
