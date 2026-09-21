<?php

namespace App\Jobs\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\Inventory\PurchaseOrder;
use App\Models\User;

class ExportPurchaseOrderJob extends AbstractExcelExportJob
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
        $query = PurchaseOrder::query()
            ->where('purchase_orders.business_id', $this->businessId)
            ->leftJoin('outlets', 'purchase_orders.outlet_id', '=', 'outlets.id')
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users', 'purchase_orders.created_by', '=', 'users.id')
            ->select([
                'purchase_orders.po_number',
                'purchase_orders.reference_number',
                'purchase_orders.order_date',
                'purchase_orders.status',
                'purchase_orders.total_amount',
                'purchase_orders.notes',
                'outlets.name as outlet_name',
                'suppliers.name as supplier_name',
                'users.name as creator_name',
            ]);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('purchase_orders.po_number', 'ilike', "%{$search}%")
                    ->orWhere('purchase_orders.reference_number', 'ilike', "%{$search}%");
            });
        }

        if (! empty($this->filters['status'])) {
            $query->where('purchase_orders.status', $this->filters['status']);
        }

        if (! empty($this->filters['supplier_id'])) {
            $query->where('purchase_orders.supplier_id', $this->filters['supplier_id']);
        }

        if (! empty($this->filters['outlet_id'])) {
            $query->where('purchase_orders.outlet_id', $this->filters['outlet_id']);
        }

        if (! empty($this->filters['start_date'])) {
            $query->whereDate('purchase_orders.order_date', '>=', $this->filters['start_date']);
        }

        if (! empty($this->filters['end_date'])) {
            $query->whereDate('purchase_orders.order_date', '<=', $this->filters['end_date']);
        }

        $sort = $this->filters['sort'] ?? 'purchase_orders.created_at';
        $direction = $this->filters['direction'] ?? 'desc';

        return $query->orderBy($sort, $direction);
    }

    public function getHeaders(): array
    {
        return [
            'Nomor PO',
            'No. Referensi Supplier',
            'Tanggal Pesan',
            'Outlet',
            'Supplier',
            'Status',
            'Total Pembelian',
            'Dibuat Oleh',
            'Catatan',
        ];
    }

    public function mapRow($row): array
    {
        $statusEnum = is_string($row->status) ? PurchaseOrderStatus::tryFrom($row->status) : $row->status;
        $statusLabel = $statusEnum?->label() ?? (string) $row->status;

        return [
            $row->po_number ?? '-',
            $row->reference_number ?? '-',
            $row->order_date ? date('d/m/Y', strtotime($row->order_date)) : '-',
            $row->outlet_name ?? '-',
            $row->supplier_name ?? '-',
            $statusLabel,
            (float) ($row->total_amount ?? 0),
            $row->creator_name ?? '-',
            $row->notes ?? '-',
        ];
    }

    public function getModuleName(): string
    {
        return 'Pembelian Persediaan';
    }

    public function getFileName(): string
    {
        return 'pembelian_export_'.time().'.csv';
    }
}
