<?php

namespace App\Jobs\Employee;

use App\Jobs\ImportExport\AbstractExcelExportJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ExportEmployeeJob extends AbstractExcelExportJob
{
    public function __construct(
        User $user,
        protected array $filters = []
    ) {
        parent::__construct($user);
        $this->user = $user;
    }

    public function getQuery(): Builder
    {
        $user = $this->user ?? User::find($this->userId);
        $businessId = $user?->business_id;

        $query = User::query()
            ->where('business_id', $businessId)
            ->with(['roles:id,name,label', 'outlets:id,name']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['role'])) {
            $role = $this->filters['role'];
            $query->whereHas('roles', function (Builder $q) use ($role) {
                $q->where('roles.name', $role);
            });
        }

        if (! empty($this->filters['outlet'])) {
            $outlet = $this->filters['outlet'];
            $query->whereHas('outlets', function (Builder $q) use ($outlet) {
                $q->where('outlets.id', $outlet);
            });
        }

        if (! empty($this->filters['is_deleted'])) {
            $query->onlyTrashed();
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function getHeaders(): array
    {
        return [
            'Nama Lengkap',
            'Email',
            'Nomor Telepon',
            'Peran',
            'Akses Outlet',
            'Status Akun',
            'Terdaftar Pada',
        ];
    }

    public function mapRow($row): array
    {
        $roleName = $row->is_root_user
            ? 'Pemilik Usaha (Owner)'
            : ($row->roles->first()?->label ?? $row->roles->first()?->name ?? '-');

        $outlets = $row->is_root_user
            ? 'Semua Outlet'
            : ($row->outlets->pluck('name')->implode(', ') ?: '-');

        return [
            $row->name,
            $row->email,
            $row->phone ?? '-',
            $roleName,
            $outlets,
            $row->deleted_at ? 'Arsip' : 'Aktif',
            $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function getModuleName(): string
    {
        return 'Pegawai';
    }

    public function getFileName(): string
    {
        return 'pegawai_export_'.time().'.xlsx';
    }
}
