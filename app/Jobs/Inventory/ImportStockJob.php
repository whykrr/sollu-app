<?php

namespace App\Jobs\Inventory;

use App\Enums\InventoryMovementType;
use App\Jobs\ImportExport\AbstractExcelImportJob;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Outlet;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class ImportStockJob extends AbstractExcelImportJob
{
    protected $businessId;

    public function __construct(User $user, string $filePath, $businessId)
    {
        parent::__construct($user, $filePath);
        $this->businessId = $businessId;
    }

    public function getModuleName(): string
    {
        return 'Stok Inventori';
    }

    public function processRow(array $row): void
    {
        $outletName = trim($row['Outlet'] ?? '');
        $name = trim($row['Nama'] ?? '');
        $sku = trim($row['SKU'] ?? '');
        $barcode = trim($row['Barcode'] ?? '');
        $stokAwalStr = trim((string) ($row['Stok Awal'] ?? ''));
        $hargaBeliStr = trim((string) ($row['Harga Beli'] ?? ''));

        if (empty($outletName)) {
            throw new Exception('Nama Outlet wajib diisi pada CSV.');
        }

        $outlet = Outlet::where('business_id', $this->businessId)
            ->whereRaw('LOWER(name) = ?', [strtolower($outletName)])
            ->first();

        if (! $outlet) {
            throw new Exception("Outlet '{$outletName}' tidak ditemukan di sistem.");
        }

        // Locate InventoryItem by SKU, Barcode, or Name
        $item = null;
        if (! empty($sku)) {
            $item = InventoryItem::where('business_id', $this->businessId)
                ->where('sku', $sku)
                ->first();
        }

        if (! $item && ! empty($barcode)) {
            $item = InventoryItem::where('business_id', $this->businessId)
                ->where('barcode', $barcode)
                ->first();
        }

        if (! $item && ! empty($name)) {
            $item = InventoryItem::where('business_id', $this->businessId)
                ->where('name', $name)
                ->first();
        }

        if (! $item) {
            throw new Exception("Item '{$name}' (SKU: '{$sku}') tidak ditemukan.");
        }

        // Update SKU & Barcode
        if (! empty($sku) && $item->sku !== $sku) {
            $skuExists = InventoryItem::where('business_id', $this->businessId)
                ->where('sku', $sku)
                ->where('id', '!=', $item->id)
                ->exists();

            if ($skuExists) {
                throw new Exception("SKU '{$sku}' sudah digunakan oleh produk/item lain.");
            }
            $item->sku = $sku;
        }

        if (! empty($barcode) && $item->barcode !== $barcode) {
            $barcodeExists = InventoryItem::where('business_id', $this->businessId)
                ->where('barcode', $barcode)
                ->where('id', '!=', $item->id)
                ->exists();

            if ($barcodeExists) {
                throw new Exception("Barcode '{$barcode}' sudah digunakan oleh produk/item lain.");
            }
            $item->barcode = $barcode;
        }

        if ($item->isDirty(['sku', 'barcode'])) {
            $item->save();
        }

        // Get or Create InventoryBalance
        $balance = InventoryBalance::firstOrCreate(
            [
                'business_id' => $this->businessId,
                'outlet_id' => $outlet->id,
                'inventory_item_id' => $item->id,
            ],
            [
                'current_stock' => 0,
            ]
        );

        // Process Initial Stock & Price Validation (Requirement 7)
        if ($stokAwalStr !== '') {
            $stokAwal = (float) $stokAwalStr;
            $hargaBeli = $hargaBeliStr !== '' ? (float) $hargaBeliStr : 0;

            $hasStock = $balance->current_stock > 0;
            $hasMovements = InventoryMovement::where('inventory_item_id', $item->id)
                ->where('outlet_id', $outlet->id)
                ->exists();

            if ($hasStock || $hasMovements) {
                throw new Exception("Batal/Ditolak: Stok awal untuk '{$item->name}' di outlet '{$outlet->name}' tidak dapat diinput karena sudah memiliki stok ({$balance->current_stock}) atau riwayat mutasi.");
            }

            if ($stokAwal > 0) {
                DB::beginTransaction();
                try {
                    $balance->current_stock = $stokAwal;
                    $balance->save();

                    $movement = InventoryMovement::create([
                        'business_id' => $this->businessId,
                        'outlet_id' => $outlet->id,
                        'inventory_item_id' => $item->id,
                        'movement_type' => InventoryMovementType::Adjustment->value,
                        'qty_change' => $stokAwal,
                        'stock_before' => 0,
                        'stock_after' => $stokAwal,
                        'description' => 'Input Stok Awal (Impor CSV)',
                        'created_by' => $this->user->id,
                    ]);

                    InventoryCostLayer::create([
                        'inventory_item_id' => $item->id,
                        'outlet_id' => $outlet->id,
                        'purchase_price' => $hargaBeli,
                        'qty_purchased' => $stokAwal,
                        'qty_remaining' => $stokAwal,
                        'reference_id' => $movement->id,
                    ]);

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
        } elseif ($hargaBeliStr !== '' && (float) $hargaBeliStr >= 0) {
            $hargaBeli = (float) $hargaBeliStr;
            $latestLayer = InventoryCostLayer::where('inventory_item_id', $item->id)
                ->where('outlet_id', $outlet->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestLayer) {
                $latestLayer->update(['purchase_price' => $hargaBeli]);
            }
        }
    }
}
