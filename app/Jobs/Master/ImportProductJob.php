<?php

namespace App\Jobs\Master;

use App\Jobs\ImportExport\AbstractCsvImportJob;
use App\Models\Master\ProductCategory;
use App\Models\Uom;
use App\Models\User;
use App\Notifications\CsvImportCompleted;
use App\Services\Master\ProductService;
use Illuminate\Support\Facades\Storage;

class ImportProductJob extends AbstractCsvImportJob
{
    protected ProductService $productService;

    protected $businessId;

    public function __construct($user, string $filePath, string $businessId)
    {
        parent::__construct($user, $filePath);
        $this->businessId = $businessId;
    }

    protected function getModuleName(): string
    {
        return 'Produk';
    }

    protected function processRow(array $row): void
    {
        // Unused directly because we override handle() to process groups.
    }

    public function handle(): void
    {
        $this->user = User::find($this->userId);

        if (! $this->user) {
            return;
        }

        $this->productService = app(ProductService::class);

        if (! Storage::disk('local')->exists($this->filePath)) {
            return;
        }

        $stream = Storage::disk('local')->readStream($this->filePath);

        // Skip BOM if present
        $bom = fread($stream, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($stream);
        }

        // Detect delimiter
        $firstLine = fgets($stream);
        $delimiter = ',';
        if ($firstLine !== false) {
            $commaCount = substr_count($firstLine, ',');
            $semicolonCount = substr_count($firstLine, ';');
            if ($semicolonCount > $commaCount) {
                $delimiter = ';';
            }
        }

        rewind($stream);
        $bom = fread($stream, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($stream);
        }

        $headers = fgetcsv($stream, 0, $delimiter);
        if (! $headers) {
            fclose($stream);

            return;
        }

        $headers = array_map('trim', $headers);

        $successCount = 0;
        $failedRows = [];

        $currentParentData = null;
        $currentParentRows = [];

        while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }

            $rowData = [];
            foreach ($headers as $index => $headerName) {
                $rowData[$headerName] = isset($row[$index]) ? trim($row[$index]) : null;
            }

            $nama = trim($rowData['Nama Produk'] ?? '');

            if (! empty($nama)) {
                // Save previous parent
                if ($currentParentData) {
                    try {
                        $existingProduct = \App\Models\Master\Product::where('business_id', $this->businessId)
                            ->where('name', $currentParentData['name'])
                            ->first();

                        if ($existingProduct) {
                            if ($existingProduct->has_variant && ! $currentParentData['has_variant']) {
                                throw new \Exception('Penonaktifan varian tidak dapat dilakukan melalui Impor. Silakan lakukan aksi ini secara manual melalui form produk di aplikasi.');
                            }
                            $this->productService->updateProduct($existingProduct, $currentParentData);
                        } else {
                            $this->productService->createProduct($currentParentData);
                        }
                        $successCount += count($currentParentRows);
                    } catch (\Exception $e) {
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
                } catch (\Exception $e) {
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
                    } catch (\Exception $e) {
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
                $existingProduct = \App\Models\Master\Product::where('business_id', $this->businessId)
                    ->where('name', $currentParentData['name'])
                    ->first();

                if ($existingProduct) {
                    if ($existingProduct->has_variant && ! $currentParentData['has_variant']) {
                        throw new \Exception('Penonaktifan varian tidak dapat dilakukan melalui Impor. Silakan lakukan aksi ini secara manual melalui form produk di aplikasi.');
                    }
                    $this->productService->updateProduct($existingProduct, $currentParentData);
                } else {
                    $this->productService->createProduct($currentParentData);
                }
                $successCount += count($currentParentRows);
            } catch (\Exception $e) {
                foreach ($currentParentRows as $r) {
                    $r['Error Message'] = $e->getMessage();
                    $failedRows[] = $r;
                }
            }
        }

        fclose($stream);
        Storage::disk('local')->delete($this->filePath);

        $failedUrl = null;
        $failedCount = count($failedRows);

        if ($failedCount > 0) {
            Storage::disk('local')->makeDirectory('exports');
            $failedFileName = 'failed_import_'.time().'.csv';
            $failedFilePath = 'exports/'.$failedFileName;

            $failedFile = fopen('php://temp', 'w+');
            fwrite($failedFile, chr(0xEF).chr(0xBB).chr(0xBF));

            $failedHeaders = array_keys($failedRows[0]);
            fputcsv($failedFile, $failedHeaders, $delimiter);

            foreach ($failedRows as $failedRow) {
                fputcsv($failedFile, $failedRow, $delimiter);
            }

            rewind($failedFile);
            $content = stream_get_contents($failedFile);
            fclose($failedFile);

            Storage::disk('local')->put($failedFilePath, $content);
            $failedUrl = route('exports.download', ['file' => $failedFileName]);
        }

        $expiresAt = $failedCount > 0 ? now()->addDays(1) : null;
        $this->user->notify(new CsvImportCompleted(
            $this->getModuleName(),
            $successCount,
            $failedCount,
            $failedUrl,
            $expiresAt
        ));
    }

    protected function buildParentData(array $row): array
    {
        $kode = $row['SKU'] ?? '';
        $barcode = $row['Barcode'] ?? '';
        $nama = $row['Nama Produk'] ?? '';
        $kategori = $row['Kategori'] ?? '';
        $deskripsi = $row['Deskripsi'] ?? '';
        $tipe = strtolower($row['Tipe Produk'] ?? '') ?: 'basic';
        $v1Name = $row['Nama Varian 1'] ?? '';
        $v1Opt = $row['Opsi Varian 1'] ?? '';
        $v2Name = $row['Nama Varian 2'] ?? '';
        $v2Opt = $row['Opsi Varian 2'] ?? '';
        $harga = (float) ($row['Harga Dasar'] ?? 0);
        $satuan = $row['Satuan'] ?? '';
        $lacakStok = strtolower($row['Lacak Stok'] ?? 'ya') === 'ya';
        $minStok = (float) ($row['Minimum Stok'] ?? 0);
        $statusTampil = strtolower($row['Status Tampil'] ?? 'ya') === 'ya';

        // Category
        $categoryId = null;
        if (! empty($kategori)) {
            $cat = ProductCategory::firstOrCreate(
                ['business_id' => $this->businessId, 'name' => $kategori],
                ['name' => $kategori]
            );
            $categoryId = $cat->id;
        }

        // UOM
        $uomId = null;
        if (! empty($satuan) && $lacakStok) {
            $uom = Uom::where('name', $satuan)->first();
            if ($uom) {
                $uomId = $uom->id;
            } else {
                throw new \Exception("Satuan '{$satuan}' tidak ditemukan.");
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
            'min_stock' => $minStok,
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
        $activeOutlets = \App\Models\Outlet::where('business_id', $this->businessId)->active()->get();
        foreach ($activeOutlets as $outlet) {
            $colName = 'Outlet: '.$outlet->name;
            if (isset($row[$colName]) && strtolower($row[$colName]) === 'ya') {
                $data['outlets'][] = [
                    'outlet_id' => $outlet->id,
                    'is_enabled' => true,
                    'is_available' => true,
                ];
            }
        }

        // If parent has variant info, process it
        if (! empty($v1Name) && ! empty($v1Opt)) {
            $this->buildChildData($data, $row);
        }

        return $data;
    }

    protected function buildChildData(array &$data, array $row)
    {
        $kode = $row['SKU'] ?? '';
        $barcode = $row['Barcode'] ?? '';
        $v1Name = $row['Nama Varian 1'] ?? '';
        $v1Opt = $row['Opsi Varian 1'] ?? '';
        $v2Name = $row['Nama Varian 2'] ?? '';
        $v2Opt = $row['Opsi Varian 2'] ?? '';

        $hargaStr = trim((string) ($row['Harga Dasar'] ?? ''));
        $harga = $hargaStr !== '' ? (float) $hargaStr : $data['base_price'];

        $minStokStr = trim((string) ($row['Minimum Stok'] ?? ''));
        $minStok = $minStokStr !== '' ? (float) $minStokStr : $data['min_stock'];

        if (empty($v1Name) || empty($v1Opt)) {
            throw new \Exception('Varian 1 (Nama & Opsi) harus diisi untuk baris anak.');
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
            'min_stock' => $minStok,
        ];
    }
}
