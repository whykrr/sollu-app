<?php

namespace App\Jobs\Inventory;

use App\Enums\InventoryMovementType;
use App\Jobs\ImportExport\AbstractExcelImportJob;
use App\Models\Business;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Inventory\InventoryCostingService;
use Exception;

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
                ->whereHas('productItem', fn ($q) => $q->where('sku', $sku))
                ->first();
        }

        if (! $item && ! empty($barcode)) {
            $item = InventoryItem::where('business_id', $this->businessId)
                ->whereHas('productItem', fn ($q) => $q->where('barcode', $barcode))
                ->first();
        }

        if (! $item && ! empty($name)) {
            $item = InventoryItem::where('business_id', $this->businessId)
                ->whereHas('productItem', fn ($q) => $q->where('name', $name))
                ->first();
        }

        if (! $item) {
            throw new Exception("Item '{$name}' (SKU: '{$sku}') tidak ditemukan.");
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
                'minimum_stock' => $item->minimum_stock ?? 0,
            ]
        );

        // Update Minimum Stock for this specific outlet balance
        $minStockStr = trim((string) ($row['Minimum Stok'] ?? $row['Minimal Stok'] ?? $row['minimum_stock'] ?? ''));
        if ($minStockStr !== '' && is_numeric($minStockStr)) {
            $minStock = (float) $minStockStr;
            if ($minStock >= 0 && (float) $balance->minimum_stock !== $minStock) {
                $balance->update(['minimum_stock' => $minStock]);
            }
        }

        // Process Initial Stock & Price Validation (Requirement 7)
        if ($stokAwalStr !== '') {
            if (! is_numeric($stokAwalStr) || (float) $stokAwalStr < 0) {
                throw new Exception("Stok Awal tidak valid atau bernilai negatif untuk item '{$item->name}'.");
            }

            $stokAwal = (float) $stokAwalStr;

            if ($stokAwal > 0) {
                if ($hargaBeliStr === '' || ! is_numeric($hargaBeliStr)) {
                    throw new Exception("Harga Beli wajib diisi saat mengisi Stok Awal untuk item '{$item->name}'.");
                }

                $hargaBeli = (float) $hargaBeliStr;

                if ($hargaBeli < 0) {
                    throw new Exception("Harga Beli tidak boleh bernilai negatif untuk item '{$item->name}'.");
                }

                $hasStock = $balance->current_stock > 0;
                $hasMovements = InventoryMovement::where('inventory_item_id', $item->id)
                    ->where('outlet_id', $outlet->id)
                    ->exists();

                if ($hasStock || $hasMovements) {
                    throw new Exception("Batal/Ditolak: Stok awal untuk '{$item->name}' di outlet '{$outlet->name}' tidak dapat diinput karena sudah memiliki stok ({$balance->current_stock}) atau riwayat mutasi.");
                }

                $costingService = app(InventoryCostingService::class);
                $business = Business::find($this->businessId);

                $costingService->recordIncomingStock(
                    business: $business,
                    outlet: $outlet,
                    item: $item,
                    qty: $stokAwal,
                    unitCost: $hargaBeli,
                    movementType: InventoryMovementType::InitialStock,
                    reference: null,
                    description: 'Input Stok Awal (Impor CSV)',
                    user: $this->user
                );
            }
        } elseif ($hargaBeliStr !== '' && is_numeric($hargaBeliStr) && (float) $hargaBeliStr >= 0) {
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
