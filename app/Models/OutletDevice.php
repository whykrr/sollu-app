<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class OutletDevice extends Model
{
    use HasApiTokens, HasFactory, HasUuids;

    protected $fillable = ['outlet_id', 'device_name', 'device_type', 'serial_number', 'client_device_uuid', 'hardware_fingerprint', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
