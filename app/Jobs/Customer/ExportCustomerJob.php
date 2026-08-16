<?php

namespace App\Jobs\Customer;

use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\Master\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ExportCustomerJob extends AbstractExcelExportJob
{
    public function __construct(
        User $user,
        protected array $filters = []
    ) {
        parent::__construct($user);
    }

    public function getQuery(): Builder
    {
        $query = Customer::query()->where('business_id', $this->user->business_id);

        // apply filters
        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if (isset($this->filters['is_active'])) {
            $query->where('is_active', (bool) $this->filters['is_active']);
        }

        return $query;
    }

    public function getHeaders(): array
    {
        return ['Nama Lengkap', 'Nomor Telepon', 'Email', 'Alamat', 'Tanggal Lahir', 'Jenis Kelamin', 'Catatan', 'Status'];
    }

    public function mapRow($row): array
    {
        return [
            $row->name,
            $row->phone,
            $row->email ?? '-',
            $row->address ?? '-',
            $row->birthdate ? $row->birthdate->format('Y-m-d') : '-',
            $row->gender ? $row->gender->label() : '-',
            $row->notes ?? '-',
            $row->is_active ? 'Aktif' : 'Tidak Aktif',
        ];
    }

    public function getModuleName(): string
    {
        return 'Pelanggan';
    }

    public function getFileName(): string
    {
        return 'pelanggan_export_'.time().'.csv';
    }
}
