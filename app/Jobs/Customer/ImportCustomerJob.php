<?php

namespace App\Jobs\Customer;

use App\Enums\CustomerGender;
use App\Jobs\ImportExport\AbstractCsvImportJob;
use App\Models\Master\Customer;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportCustomerJob extends AbstractCsvImportJob
{
    protected function getModuleName(): string
    {
        return 'Pelanggan';
    }

    protected function processRow(array $row): void
    {
        $validator = Validator::make($row, [
            'Nama Lengkap' => 'required|string|max:255',
            'Nomor Telepon' => [
                'required',
                'string',
                Rule::unique('customers', 'phone')->where(function ($query) {
                    return $query->where('business_id', $this->user->business_id);
                }),
            ],
            'Email' => 'nullable|email|max:255',
            'Alamat' => 'nullable|string|max:500',
            'Tanggal Lahir' => 'nullable|date',
            'Jenis Kelamin' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'Catatan' => 'nullable|string',
            'Status' => ['nullable', Rule::in(['Aktif', 'Tidak Aktif'])],
        ]);

        if ($validator->fails()) {
            throw new Exception(implode(', ', $validator->errors()->all()));
        }

        $validated = $validator->validated();

        $gender = null;
        if (! empty($validated['Jenis Kelamin'])) {
            $gender = $validated['Jenis Kelamin'] === 'Laki-laki' ? CustomerGender::MALE : CustomerGender::FEMALE;
        }

        $isActive = true;
        if (isset($validated['Status']) && $validated['Status'] === 'Tidak Aktif') {
            $isActive = false;
        }

        Customer::create([
            'business_id' => $this->user->business_id,
            'name' => $validated['Nama Lengkap'],
            'phone' => $validated['Nomor Telepon'],
            'email' => $validated['Email'] ?: null,
            'address' => $validated['Alamat'] ?: null,
            'birthdate' => $validated['Tanggal Lahir'] ?: null,
            'gender' => $gender,
            'notes' => $validated['Catatan'] ?: null,
            'is_active' => $isActive,
            'created_by' => $this->user->id,
        ]);
    }
}
