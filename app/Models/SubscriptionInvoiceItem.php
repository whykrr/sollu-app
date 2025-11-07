<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Collection|SubscriptionInvoice $invoice
 * @property-read Collection|Outlet $outlet
 * @mixin IdeHelperSubscriptionInvoiceItem
 */
class SubscriptionInvoiceItem extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subscription_invoice_id',
        'outlet_id',
        'total',
    ];

    /**
     * Get the invoice that owns the SubscriptionInvoiceItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id');
    }

    /**
     * Get the outlet that owns the SubscriptionInvoiceItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }


}
