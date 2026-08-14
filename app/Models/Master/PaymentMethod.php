<?php

namespace App\Models\Master;

use App\Models\Business;
use App\Models\Outlet;
use App\Models\Sales\TransactionPayment;
use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $business_id
 * @property string $name
 * @property string $type
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Business $business
 * @property-read Collection|Outlet[] $outlets
 * @property-read Collection|OutletPaymentMethod[] $outletPaymentMethods
 * @property-read Collection|TransactionPayment[] $transactionPayments
 *
 * @mixin IdeHelperPaymentMethod
 */
class PaymentMethod extends Model
{
    use HasBusiness, HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'name',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class, 'outlet_payment_method')
            ->using(OutletPaymentMethod::class)
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    public function outletPaymentMethods(): HasMany
    {
        return $this->hasMany(OutletPaymentMethod::class);
    }

    public function transactionPayments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }

    /**
     * Scope query untuk mengambil metode pembayaran yang aktif untuk outlet tertentu.
     * Menggunakan fallback jika belum pernah disetting per-outlet.
     */
    public function scopeActiveForOutlet(Builder $query, string $outletId): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($outletId) {
                $q->whereHas('outletPaymentMethods', function (Builder $pivotQuery) use ($outletId) {
                    $pivotQuery->where('outlet_id', $outletId)
                        ->where('is_enabled', true);
                })
                    // Graceful fallback for backward compatibility if no outlet settings exist yet
                    ->orWhereDoesntHave('outletPaymentMethods');
            });
    }
}
