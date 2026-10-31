<?php

namespace App\Models\Master;

use App\Enums\PaymentReferencePolicy;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\Sales\TransactionPayment;
use App\Trait\HasBusiness;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $business_id
 * @property string $name
 * @property string $type
 * @property string|null $provider
 * @property string|null $account_number
 * @property string|null $account_name
 * @property PaymentReferencePolicy $reference_policy
 * @property bool $open_cash_drawer
 * @property string|null $instructions
 * @property string|null $qr_image_path
 * @property bool $is_system
 * @property bool $is_active
 * @property int $sort_order
 * @property string|null $qr_image_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
        'provider',
        'account_number',
        'account_name',
        'reference_policy',
        'open_cash_drawer',
        'instructions',
        'qr_image_path',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $appends = [
        'qr_image_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'open_cash_drawer' => 'boolean',
            'reference_policy' => PaymentReferencePolicy::class,
        ];
    }

    protected function qrImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->qr_image_path ? Storage::disk('public')->url($this->qr_image_path) : null,
        );
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

    /**
     * Scope query untuk mengurutkan metode pembayaran berdasarkan standar industri platform POS modern.
     */
    public function scopeOrderedByIndustryStandard(Builder $query): Builder
    {
        return $query->orderByRaw("
            CASE type
                WHEN 'cash' THEN 1
                WHEN 'qris' THEN 2
                WHEN 'edc' THEN 3
                WHEN 'ewallet' THEN 4
                WHEN 'bank_transfer' THEN 5
                ELSE 6
            END ASC
        ")
            ->orderByDesc('is_system')
            ->orderBy('name', 'asc');
    }
}
