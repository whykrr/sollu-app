<?php

namespace App\Models;

use App\Enums\DeviceTypeEnum;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @mixin IdeHelperOutletDevice
 */
class OutletDevice extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, SortableModel;

    protected $fillable = [
        'outlet_id',
        'device_name',
        'device_type',
        'serial_number',
        'client_device_uuid',
        'hardware_fingerprint',
        'is_active',
        'app_version',
        'platform_type',
        'unpaired_at',
        'unpaired_by',
    ];

    protected array $sortable = [
        'device_name',
        'device_type',
        'serial_number',
        'is_active',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'device_type' => DeviceTypeEnum::class,
            'unpaired_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function unpairedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unpaired_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUnpaired($query)
    {
        return $query->where('is_active', false)->whereNotNull('unpaired_at');
    }
}
