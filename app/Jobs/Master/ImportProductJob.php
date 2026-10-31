<?php

namespace App\Jobs\Master;

use App\Enums\FeatureEnum;
use App\Jobs\ImportExport\AbstractExcelImportJob;
use App\Models\Business;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Uom;
use App\Models\User;
use App\Notifications\ExcelImportCompleted;
use App\Services\App\Master\ProductService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductJob extends AbstractExcelImportJob
{
    protected ProductService $productService;

    protected $businessId;

    /**
     * @var array<string, string>
     */
    protected array $categoriesCache = [];

    /**
     * @var array<string, string>
     */
    protected array $uomsCache = [];

    /**
     * @var Collection|null
     */
    protected $activeOutletsCache = null;

    /**
     * @var array<string, Product>
     */
    protected array $existingProductsCache = [];

    /**
     * @var array<string, string>
     */
    protected array $existingSkusCache = [];

    /**
     * @var array<string, string>
     */
    protected array $existingBarcodesCache = [];

    /**
     * @var array<string, bool>
     */
    protected array $fileSkus = [];

    /**
     * @var array<string, bool>
     */
    protected array $fileBarcodes = [];

    protected bool $hasVariantFeature = true;

    protected bool $hasMultiOutletFeature = true;

    public function __construct($user, string $filePath, string $businessId)
    {
        parent::__construct($user, $filePath);
        $this->businessId = $businessId;
    }

    public function getModuleName(): string
    {
        return 'Produk';
    }

    public function processRow(array $row): void
    {
        // Unused directly because product imports require hierarchical grouping (Parent + Child Variants).
    }

    public function handle(): void
    {
        if (! $this->user) {
            $this->user = User::find($this->userId);
        }

        if (! $this->user) {
            return;
        }

        $this->productService = app(ProductService::class);

        if (! Storage::disk('local')->exists($this->filePath)) {
            return;
        }

        $fullPath = Storage::disk('local')->path($this->filePath);
        $sheet = [];

        // Memory-safe stream reading for CSV; fallback to Excel reader for XLSX/XLS
        if (str_ends_with(strtolower($this->filePath), '.csv')) {
            if (($handle = fopen($fullPath, 'r')) !== false) {
                while (($data = fgetcsv($handle)) !== false) {
                    $sheet[] = $data;
                }
                fclose($handle);
            }
        } else {
            $sheets = Excel::toArray(new class {}, $fullPath);
            $sheet = $sheets[0] ?? [];
        }

        if (empty($sheet)) {
            Storage::disk('local')->delete($this->filePath);

            return;
        }

        $headers = array_map('trim', $sheet[0] ?? []);
        $dataRows = array_slice($sheet, 1);

        // Preload tenant SaaS features
        $business = Business::findCached($this->businessId);
        $this->hasVariantFeature = $business ? $business->hasFeature(FeatureEnum::PRODUCT_VARIANT) : true;
        $this->hasMultiOutletFeature = $business ? $business->hasFeature(FeatureEnum::MULTI_OUTLET) : true;

        // In-memory preloading of lookups for O(1) loop operations without DB queries
        $this->activeOutletsCache = Outlet::where('business_id', $this->businessId)->active()->get();
        $this->uomsCache = Uom::pluck('id', 'name')->all();
        $this->categoriesCache = ProductCategory::where('business_id', $this->businessId)->pluck('id', 'name')->all();

        $this->existingProductsCache = Product::where('business_id', $this->businessId)
            ->select(['id', 'business_id', 'name', 'code', 'has_variant', 'product_type'])
            ->get()
            ->keyBy('name')
            ->all();

        $this->existingSkusCache = ProductItem::where('business_id', $this->businessId)
            ->whereNull('deleted_at')
            ->whereNotNull('sku')
            ->pluck('product_id', 'sku')
            ->all();

        $this->existingBarcodesCache = ProductItem::where('business_id', $this->businessId)
            ->whereNull('deleted_at')
            ->whereNotNull('barcode')
            ->pluck('product_id', 'barcode')
            ->all();

        $successCount = 0;
        $failedRows = [];

        $currentParentData = null;
        $currentParentRows = [];

        foreach ($dataRows as $row) {
            if (empty(array_filter($row, fn ($val) => $val !== null && $val !== ''))) {
                continue;
            }

            $rowData = [];
            foreach ($headers as $index => $headerName) {
                $rowData[$headerName] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $nama = trim($rowData['Nama Produk'] ?? '');

            if (! empty($nama)) {
                // Save previous parent
                if ($currentParentData) {
                    try {
                        $this->saveParentProduct($currentParentData);
                        $successCount += count($currentParentRows);
                    } catch (Exception $e) {
                        foreach ($currentParentRows as $r) {
                            $r['Error Message'] = $e->getMessage();
                            $failedRows[] = $r;
                        }
                    }
                }

                // Start new parent
                $currentParentRows = [$rowData];
                try {
                    $currentParentData = $this->buildParentData($rowData);
                } catch (Exception $e) {
                    $rowData['Error Message'] = $e->getMessage();
                    $failedRows[] = $rowData;
                    $currentParentData = null;
                    $currentParentRows = [];
                }
            } else {
                // Child row
                if ($currentParentData) {
                    $currentParentRows[] = $rowData;
                    try {
                        $this->buildChildData($currentParentData, $rowData);
                    } catch (Exception $e) {
                        // Fail the whole parent if child fails
                        foreach ($currentParentRows as $r) {
                            $r['Error Message'] = 'Error Varian: '.$e->getMessage();
                            $failedRows[] = $r;
                        }
                        $currentParentData = null;
                        $currentParentRows = [];
                    }
                } else {
                    $rowData['Error Message'] = 'Baris varian ditemukan tanpa baris induk sebelumnya yang valid.';
                    $failedRows[] = $rowData;
                }
            }
        }

        // Save the last parent
        if ($currentParentData) {
            try {
                $this->saveParentProduct($currentParentData);
                $successCount += count($currentParentRows);
            } catch (Exception $e) {
                foreach ($currentParentRows as $r) {
                    $r['Error Message'] = $e->getMessage();
                    $failedRows[] = $r;
                }
            }
        }

        Storage::disk('local')->delete($this->filePath);

        $failedUrl = null;
        $failedCount = count($failedRows);

        if ($failedCount > 0) {
            Storage::disk('public')->makeDirectory('exports');
            $failedFileName = 'failed_import_'.time().'.xlsx';
            $failedFilePath = 'exports/'.$failedFileName;

            $exportFailed = new class($failedRows) implements FromArray
            {
                public function __construct(private array $data) {}

                public function array(): array
                {
                    if (empty($this->data)) {
                        return [];
                    }
                    $headers = array_keys($this->data[0]);

                    return array_merge([$headers], $this->data);
                }
            };

            Excel::store($exportFailed, $failedFilePath, 'public', \Maatwebsite\Excel\Excel::XLSX);
            $failedUrl = route('exports.download', ['file' => $failedFileName]);
        }

        $expiresAt = $failedCount > 0 ? now()->addDays(1) : null;
        $this->user->notify(new ExcelImportCompleted(
            $this->getModuleName(),
            $successCount,
            $failedCount,
            $failedUrl,
            $expiresAt
        ));
    }

    protected function saveParentProduct(array $data): void
    {
        $existingProduct = $this->existingProductsCache[$data['name']] ?? null;

        if ($existingProduct) {
            if ($existingProduct->has_variant && ! $data['has_variant']) {
                throw new Exception('Penonaktifan varian tidak dapat dilakukan melalui Impor. Silakan lakukan aksi ini secara manual melalui form produk di aplikasi.');
            }
            $updated = $this->productService->updateProduct($existingProduct, $data);
            $this->recordProcessedCodes($updated, $data);
        } else {
            $created = $this->productService->createProduct($data);
            $this->existingProductsCache[$data['name']] = $created;
            $this->recordProcessedCodes($created, $data);
        }
    }

    protected function recordProcessedCodes(Product $product, array $data): void
    {
        if (! empty($data['code'])) {
            $this->existingSkusCache[$data['code']] = $product->id;
        }
        if (! empty($data['barcode'])) {
            $this->existingBarcodesCache[$data['barcode']] = $product->id;
        }
        if (! empty($data['variant_combinations'])) {
            foreach ($data['variant_combinations'] as $combo) {
                if (! empty($combo['sku'])) {
                    $this->existingSkusCache[$combo['sku']] = $product->id;
                }
                if (! empty($combo['barcode'])) {
                    $this->existingBarcodesCache[$combo['barcode']] = $product->id;
                }
            }
        }
    }

    protected function buildParentData(array $row): array
    {
        $kode = trim($row['SKU'] ?? '');
        $barcode = trim($row['Barcode'] ?? '');
        $nama = trim($row['Nama Produk'] ?? '');
        $kategori = trim($row['Kategori'] ?? '');
        $deskripsi = trim($row['Deskripsi'] ?? '');
        $tipe = strtolower(trim($row['Tipe Produk'] ?? '')) ?: 'basic';
        $v1Name = trim($row['Nama Varian 1'] ?? '');
        $v1Opt = trim($row['Opsi Varian 1'] ?? '');
        $harga = (float) ($row['Harga Dasar'] ?? 0);
        $satuan = trim($row['Satuan'] ?? '');
        $lacakStok = strtolower(trim($row['Lacak Stok'] ?? 'ya')) === 'ya';
        $minStok = (float) ($row['Minimum Stok'] ?? 0);
        $statusTampil = strtolower(trim($row['Status Tampil'] ?? 'ya')) === 'ya';

        // Check SKU & Barcode cross-field collisions and uniqueness
        $existingProduct = $this->existingProductsCache[$nama] ?? null;

        if (! empty($kode)) {
            if (isset($this->existingSkusCache[$kode]) && $this->existingSkusCache[$kode] !== $existingProduct?->id) {
                throw new Exception("Kode SKU '{$kode}' sudah digunakan oleh produk lain.");
            }
            if (isset($this->existingBarcodesCache[$kode]) && $this->existingBarcodesCache[$kode] !== $existingProduct?->id) {
                throw new Exception("Kode SKU '{$kode}' tidak boleh sama dengan Barcode produk lain.");
            }
            if (isset($this->fileSkus[$kode])) {
                throw new Exception("Kode SKU '{$kode}' duplikat di dalam file impor.");
            }
            $this->fileSkus[$kode] = true;
        }

        if (! empty($barcode)) {
            if (isset($this->existingBarcodesCache[$barcode]) && $this->existingBarcodesCache[$barcode] !== $existingProduct?->id) {
                throw new Exception("Barcode '{$barcode}' sudah digunakan oleh produk lain.");
            }
            if (isset($this->existingSkusCache[$barcode]) && $this->existingSkusCache[$barcode] !== $existingProduct?->id) {
                throw new Exception("Barcode '{$barcode}' tidak boleh sama dengan Kode SKU produk lain.");
            }
            if (! empty($kode) && $kode === $barcode) {
                throw new Exception("Barcode '{$barcode}' tidak boleh sama dengan Kode SKU pada baris yang sama.");
            }
            if (isset($this->fileBarcodes[$barcode])) {
                throw new Exception("Barcode '{$barcode}' duplikat di dalam file impor.");
            }
            $this->fileBarcodes[$barcode] = true;
        }

        // SaaS Feature Gating: Variant check
        $hasVariantInRow = ! empty($v1Name) && ! empty($v1Opt);
        if ($hasVariantInRow && ! $this->hasVariantFeature) {
            throw new Exception('Fitur varian produk tidak tersedia pada paket langganan Anda. Silakan upgrade paket untuk mengimpor produk dengan varian.');
        }

        // Category lookup (in-memory)
        $categoryId = null;
        if (! empty($kategori)) {
            if (! isset($this->categoriesCache[$kategori])) {
                $cat = ProductCategory::create([
                    'business_id' => $this->businessId,
                    'name' => $kategori,
                ]);
                $this->categoriesCache[$kategori] = $cat->id;
            }
            $categoryId = $this->categoriesCache[$kategori];
        }

        // UOM lookup (in-memory)
        $uomId = null;
        if (! empty($satuan) && $lacakStok) {
            if (isset($this->uomsCache[$satuan])) {
                $uomId = $this->uomsCache[$satuan];
            } else {
                throw new Exception("Satuan '{$satuan}' tidak ditemukan pada sistem.");
            }
        }

        $data = [
            'business_id' => $this->businessId,
            'code' => $kode,
            'barcode' => $barcode,
            'name' => $nama,
            'product_category_id' => $categoryId,
            'description' => $deskripsi,
            'product_type' => $tipe,
            'base_price' => $harga,
            'track_inventory' => $lacakStok,
            'minimum_stock' => $minStok,
            'uom_id' => $uomId,
            'is_show' => $statusTampil,
            'has_variant' => false,
            'has_modifier' => false,
            'has_recipe' => false,
            'sellable' => true,
            'purchasable' => false,
            'variants' => [],
            'variant_combinations' => [],
            'outlets' => [],
        ];

        // Process Outlets
        if ($this->hasMultiOutletFeature && $this->activeOutletsCache) {
            foreach ($this->activeOutletsCache as $outlet) {
                $colName = 'Outlet: '.$outlet->name;
                if (isset($row[$colName]) && strtolower($row[$colName]) === 'ya') {
                    $data['outlets'][] = [
                        'outlet_id' => $outlet->id,
                        'is_enabled' => true,
                        'is_available' => true,
                    ];
                }
            }
        }

        // If parent has variant info, process it
        if ($hasVariantInRow) {
            $this->buildChildData($data, $row);
        }

        return $data;
    }

    protected function buildChildData(array &$data, array $row): void
    {
        if (! $this->hasVariantFeature) {
            throw new Exception('Fitur varian produk tidak tersedia pada paket langganan Anda. Silakan upgrade paket untuk mengimpor produk dengan varian.');
        }

        $kode = trim($row['SKU'] ?? '');
        $barcode = trim($row['Barcode'] ?? '');
        $v1Name = trim($row['Nama Varian 1'] ?? '');
        $v1Opt = trim($row['Opsi Varian 1'] ?? '');
        $v2Name = trim($row['Nama Varian 2'] ?? '');
        $v2Opt = trim($row['Opsi Varian 2'] ?? '');

        $hargaStr = trim((string) ($row['Harga Dasar'] ?? ''));
        $harga = $hargaStr !== '' ? (float) $hargaStr : $data['base_price'];

        $minStokStr = trim((string) ($row['Minimum Stok'] ?? ''));
        $minStok = $minStokStr !== '' ? (float) $minStokStr : ($data['minimum_stock'] ?? 0);

        if (empty($v1Name) || empty($v1Opt)) {
            throw new Exception('Varian 1 (Nama & Opsi) harus diisi untuk baris anak.');
        }

        $existingProduct = $this->existingProductsCache[$data['name']] ?? null;

        if (! empty($kode)) {
            if (isset($this->existingSkusCache[$kode]) && $this->existingSkusCache[$kode] !== $existingProduct?->id) {
                throw new Exception("Kode SKU '{$kode}' sudah digunakan oleh produk lain.");
            }
            if (isset($this->existingBarcodesCache[$kode]) && $this->existingBarcodesCache[$kode] !== $existingProduct?->id) {
                throw new Exception("Kode SKU '{$kode}' tidak boleh sama dengan Barcode produk lain.");
            }
            if (isset($this->fileSkus[$kode])) {
                throw new Exception("Kode SKU '{$kode}' duplikat di antara varian pada file impor.");
            }
            $this->fileSkus[$kode] = true;
        }

        if (! empty($barcode)) {
            if (isset($this->existingBarcodesCache[$barcode]) && $this->existingBarcodesCache[$barcode] !== $existingProduct?->id) {
                throw new Exception("Barcode '{$barcode}' sudah digunakan oleh produk lain.");
            }
            if (isset($this->existingSkusCache[$barcode]) && $this->existingSkusCache[$barcode] !== $existingProduct?->id) {
                throw new Exception("Barcode '{$barcode}' tidak boleh sama dengan Kode SKU produk lain.");
            }
            if (! empty($kode) && $kode === $barcode) {
                throw new Exception("Barcode '{$barcode}' tidak boleh sama dengan Kode SKU varian pada baris yang sama.");
            }
            if (isset($this->fileBarcodes[$barcode])) {
                throw new Exception("Barcode '{$barcode}' duplikat di antara varian pada file impor.");
            }
            $this->fileBarcodes[$barcode] = true;
        }

        $data['has_variant'] = true;

        $options = [];
        $options[$v1Name] = $v1Opt;

        // Variant group 1
        $vg1Index = collect($data['variants'])->search(fn ($g) => $g['name'] === $v1Name);
        if ($vg1Index === false) {
            $data['variants'][] = ['name' => $v1Name, 'options' => [['name' => $v1Opt]]];
        } else {
            $opt1Index = collect($data['variants'][$vg1Index]['options'])->search(fn ($o) => $o['name'] === $v1Opt);
            if ($opt1Index === false) {
                $data['variants'][$vg1Index]['options'][] = ['name' => $v1Opt];
            }
        }

        // Variant group 2
        if (! empty($v2Name) && ! empty($v2Opt)) {
            $options[$v2Name] = $v2Opt;
            $vg2Index = collect($data['variants'])->search(fn ($g) => $g['name'] === $v2Name);
            if ($vg2Index === false) {
                $data['variants'][] = ['name' => $v2Name, 'options' => [['name' => $v2Opt]]];
            } else {
                $opt2Index = collect($data['variants'][$vg2Index]['options'])->search(fn ($o) => $o['name'] === $v2Opt);
                if ($opt2Index === false) {
                    $data['variants'][$vg2Index]['options'][] = ['name' => $v2Opt];
                }
            }
        }

        $data['variant_combinations'][] = [
            'options' => $options,
            'sku' => $kode,
            'barcode' => $barcode,
            'price' => $harga,
            'minimum_stock' => $minStok,
        ];
    }
}
