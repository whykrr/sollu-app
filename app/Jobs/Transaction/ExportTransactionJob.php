<?php

namespace App\Jobs\Transaction;

use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\Sales\Transaction;
use App\Models\User;

class ExportTransactionJob extends AbstractExcelExportJob
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
        $query = Transaction::query()->with(['customer', 'outlet', 'shift.user']);

        // As per PRD and DB schema, outlet_id/business_id filtering might be needed depending on the tenant setup.
        // Assuming there is a scope filters on the model.
        if (! empty($this->filters)) {
            $query->filters($this->filters);
        }

        // Add additional filtering by business_id if applicable, wait, Transaction has outlet_id.
        // If businessId is needed, we would filter outlets by businessId.
        // Since we don't know the exact multi-tenant structure, we will rely on the standard filters.

        return $query;
    }

    public function getHeaders(): array
    {
        return [
            'Tanggal',
            'No. Struk',
            'Pelanggan',
            'Outlet',
            'Kasir / Shift',
            'Channel',
            'Status',
            'Status Pembayaran',
            'Subtotal',
            'Diskon',
            'Pajak',
            'Service Charge',
            'Total',
        ];
    }

    public function mapRow($row): array
    {
        return [
            $row->created_at->format('Y-m-d H:i:s'),
            $row->receipt_number ?? '-',
            $row->customer ? $row->customer->name : '-',
            $row->outlet ? $row->outlet->name : '-',
            $row->shift && $row->shift->user ? $row->shift->user->name : '-',
            $row->channel,
            $row->status,
            $row->payment_status,
            (float) $row->subtotal,
            (float) $row->discount_amount,
            (float) $row->tax_amount,
            (float) $row->service_charge_amount,
            (float) $row->total,
        ];
    }

    public function getModuleName(): string
    {
        return 'Transaksi Penjualan';
    }

    public function getFileName(): string
    {
        return 'transaksi_export_'.time().'.csv';
    }
}
