<?php

namespace App\Models\Master;

use App\Models\Outlet;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $id
 * @property string $outlet_id
 * @property string $payment_method_id
 * @property bool $is_enabled
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Outlet $outlet
 * @property-read PaymentMethod $paymentMethod
 *
 * @mixin IdeHelperOutletPaymentMethod
 */
class OutletPaymentMethod extends Pivot
{
    use HasUuids;

    protected $table = 'outlet_payment_method';

    public $incrementing = false;

    protected $fillable = [
        'outlet_id',
        'payment_method_id',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
