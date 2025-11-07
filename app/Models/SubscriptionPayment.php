<?php

namespace App\Models;

use App\Enum\SubscriptionPayment\PaymentMethod;
use App\Enum\SubscriptionPayment\PaymentType;
use App\Enum\SubscriptionPayment\Status;
use App\Trait\HasMerchant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSubscriptionPayment
 */
class SubscriptionPayment extends Model
{
    use HasFactory;
    use HasUuids;
    use HasMerchant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'merchant_id',
        'invoice_id',
        'amount',
        'payment_type',
        'order_id',
        'transaction_id',
        'payment_method',
        'status',
        'paid_at',
        'json_request',
        'json_respond',
        'json_notification',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'payment_type'      => PaymentType::class,
        'payment_method'    => PaymentMethod::class,
        'status'            => Status::class,
        'json_request'      => 'json',
        'json_respond'      => 'json',
        'json_notification' => 'json',
    ];

    /**
     * Get the invoice that owns the SubscriptionPayment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'invoice_id');
    }
}
