<?php

namespace App\Jobs\Inventory;

use App\Jobs\ImportExport\AbstractExcelImportJob;
use App\Models\Business;
use App\Models\Inventory\InventoryItem;
use App\Models\Uom;
use App\Models\User;
use App\Services\Inventory\RawMaterialService;
use Exception;

class ImportRawMaterialJob extends AbstractExcelImportJob
{
    protected $businessId;

    public function __construct(User $user, string $filePath, $businessId)
    {
        parent::__construct($user, $filePath);
        $this->businessId = $businessId;
    }

    public function getModuleName(): string
    {
        return 'Bahan Baku';
    }

    public function processRow(array $row): void
    {
        $name = trim($row['Nama'] ?? '');
        if (empty($name)) {
            throw new Exception('Nama bahan baku tidak boleh kosong.');
        }

        $uomName = trim($row['Satuan'] ?? '');
        $uomId = null;

        if (! empty($uomName)) {
            $uom = Uom::whereRaw('LOWER(name) = ?', [strtolower($uomName)])
                ->first();

            if (! $uom) {
                throw new Exception("Satuan (UOM) '{$uomName}' tidak ditemukan.");
            }
            $uomId = $uom->id;
        } else {
            throw new Exception('Satuan (UOM) wajib diisi.');
        }

        $trackInventory = trim(strtolower($row['Lacak Inventori'] ?? ''));
        $track = in_array($trackInventory, ['ya', 'yes', '1', 'true']) ? true : false;

        $isActive = trim(strtolower($row['Status Aktif'] ?? ''));
        $active = in_array($isActive, ['ya', 'yes', '1', 'true', '']) ? true : false;

        $minStock = floatval($row['Minimum Stok'] ?? 0);
        $sku = trim($row['SKU'] ?? '');
        $barcode = trim($row['Barcode'] ?? '');

        // Check uniqueness of SKU and Barcode manually to throw descriptive error
        if (! empty($sku)) {
            $exists = InventoryItem::where('business_id', $this->businessId)
                ->where('sku', $sku)
                ->where('name', '!=', $name)
                ->exists();
            if ($exists) {
                throw new Exception("SKU '{$sku}' sudah digunakan.");
            }
        }

        if (! empty($barcode)) {
            $exists = InventoryItem::where('business_id', $this->businessId)
                ->where('barcode', $barcode)
                ->where('name', '!=', $name)
                ->exists();
            if ($exists) {
                throw new Exception("Barcode '{$barcode}' sudah digunakan.");
            }
        }

        $item = InventoryItem::where('business_id', $this->businessId)
            ->where('item_type', 'raw_material')
            ->where('name', $name)
            ->first();

        $data = [
            'name' => $name,
            'sku' => $sku,
            'barcode' => $barcode,
            'uom_id' => $uomId,
            'minimum_stock' => $minStock,
            'track_inventory' => $track,
            'is_active' => $active,
        ];

        $service = app(RawMaterialService::class);
        $business = Business::find($this->businessId);

        if ($item) {
            $service->updateRawMaterial($item, $data);
        } else {
            $service->createRawMaterial($data, $business);
        }
    }
}
