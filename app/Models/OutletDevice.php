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
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
