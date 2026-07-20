<?php

namespace App\Jobs\Inventory;

use App\Jobs\ImportExport\AbstractCsvExportJob;
use App\Models\Inventory\InventoryItem;
use App\Models\User;

class ExportRawMaterialJob extends AbstractCsvExportJob
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
        return InventoryItem::where('business_id', $this->businessId)
            ->where('item_type', 'raw_material')
            ->with('uom:id,name')
            ->filters($this->filters)
            ->latest();
    }

    protected function getHeaders(): array
    {
        return [
            'Nama',
            'SKU',
            'Barcode',
            'Satuan',
            'Minimum Stok',
            'Lacak Inventori',
            'Status Aktif',
        ];
    }

    protected function mapRow($row): array
    {
        return [
            $row->name,
            $row->sku,
            $row->barcode,
            $row->uom ? $row->uom->name : '',
            (float) $row->minimum_stock,
            $row->track_inventory ? 'Ya' : 'Tidak',
            $row->is_active ? 'Ya' : 'Tidak',
        ];
    }

    protected function getModuleName(): string
    {
        return 'Bahan Baku';
    }

    protected function getFileName(): string
    {
        return 'bahan_baku_export_' . time() . '.csv';
    }
}
