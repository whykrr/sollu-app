<?php

namespace App\Jobs\Inventory;

use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\Inventory\InventoryItem;
use App\Models\User;

class ExportRawMaterialJob extends AbstractExcelExportJob
{
    protected $businessId;

    protected $filters;

    public function __construct(User $user, $businessId, array $filters = [])
    {
        parent::__construct($user);
        $this->businessId = $businessId;
        $this->filters = $filters;
    }

    public function getQuery()
    {
        return InventoryItem::where('business_id', $this->businessId)
            ->where('item_type', 'raw_material')
            ->with('uom:id,name')
            ->filters($this->filters)
            ->latest();
    }

    public function getHeaders(): array
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

    public function mapRow($row): array
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

    public function getModuleName(): string
    {
        return 'Bahan Baku';
    }

    public function getFileName(): string
    {
        return 'bahan_baku_export_'.time().'.csv';
    }
}
