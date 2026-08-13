<?php

namespace App\Jobs\Inventory;

use App\Jobs\ImportExport\AbstractCsvExportJob;
use App\Models\Inventory\InventoryBalance;
use App\Models\User;

class ExportStockJob extends AbstractCsvExportJob
{
    protected $businessId;

    protected $filters;

    public function __construct(User $user, $businessId, array $filters = [])
    {
        parent::__construct($user);
        $this->businessId = $businessId;
        $this->filters = $filters;
    }

    protected function getQuery()
    {
        $stockQuery = InventoryBalance::query()
            ->where('inventory_balances.business_id', $this->businessId)
            ->join('inventory_items', 'inventory_balances.inventory_item_id', '=', 'inventory_items.id')
            ->leftJoin('uoms', 'inventory_items.uom_id', '=', 'uoms.id')
            ->leftJoin('products', 'inventory_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->join('outlets', 'inventory_balances.outlet_id', '=', 'outlets.id')
            ->select([
                'inventory_balances.current_stock',
                'inventory_items.name as item_name',
                'inventory_items.item_type',
                'inventory_items.sku',
                'inventory_items.barcode',
                'inventory_items.minimum_stock',
                'inventory_items.is_active',
                'uoms.name as uom_name',
                'uoms.code as uom_code',
                'outlets.name as outlet_name',
                'product_categories.name as category_name',
            ]);

        if (! empty($this->filters['outlet_id'])) {
            $stockQuery->where('inventory_balances.outlet_id', $this->filters['outlet_id']);
        }

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $stockQuery->where(function ($q) use ($search) {
                $q->where('inventory_items.name', 'ilike', "%{$search}%")
                    ->orWhere('inventory_items.sku', 'ilike', "%{$search}%")
                    ->orWhere('inventory_items.barcode', 'ilike', "%{$search}%");
            });
        }

        if (! empty($this->filters['item_type'])) {
            $stockQuery->where('inventory_items.item_type', $this->filters['item_type']);
        }

        if (! empty($this->filters['category_id'])) {
            $stockQuery->where('products.product_category_id', $this->filters['category_id']);
        }

        if (! empty($this->filters['stock_status'])) {
            $status = $this->filters['stock_status'];
            if ($status === 'aman') {
                $stockQuery->whereRaw('inventory_balances.current_stock > inventory_items.minimum_stock');
            } elseif ($status === 'menipis') {
                $stockQuery->whereRaw('inventory_balances.current_stock > 0')
                    ->whereRaw('inventory_balances.current_stock <= inventory_items.minimum_stock');
            } elseif ($status === 'habis') {
                $stockQuery->where('inventory_balances.current_stock', '<=', 0);
            }
        }

        if (! empty($this->filters['is_active_only'])) {
            $stockQuery->where('inventory_items.is_active', true);
        }

        if (! empty($this->filters['in_stock_only'])) {
            $stockQuery->where('inventory_balances.current_stock', '>', 0);
        }

        $sort = $this->filters['sort'] ?? 'inventory_items.name';
        $direction = $this->filters['direction'] ?? 'asc';

        return $stockQuery->orderBy($sort, $direction);
    }

    protected function getHeaders(): array
    {
        return [
            'Outlet',
            'Nama',
            'SKU',
            'Barcode',
            'Tipe Item',
            'Kategori',
            'Satuan',
            'Minimum Stok',
            'Stok Awal',
            'Harga Beli',
            'Stok Saat Ini',
            'Status',
        ];
    }

    protected function mapRow($row): array
    {
        $status = 'Aman';
        if ($row->current_stock <= 0) {
            $status = 'Habis';
        } elseif ($row->current_stock <= $row->minimum_stock) {
            $status = 'Menipis';
        }

        return [
            $row->outlet_name ?? '-',
            $row->item_name ?? '-',
            $row->sku ?? '-',
            $row->barcode ?? '-',
            $row->item_type === 'raw_material' ? 'Bahan Baku' : 'Produk',
            $row->category_name ?? '-',
            $row->uom_name ?? ($row->uom_code ?? '-'),
            (float) $row->minimum_stock,
            '', // Stok Awal selalu kosong pada ekspor
            '', // Harga Beli selalu kosong pada ekspor
            (float) $row->current_stock,
            $status,
        ];
    }

    protected function getModuleName(): string
    {
        return 'Stok Inventori';
    }

    protected function getFileName(): string
    {
        return 'stok_export_'.time().'.csv';
    }
}
