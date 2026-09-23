<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $bank_name
 * @property string $account_number
 * @property string $account_name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Master\TFactory|null $use_factory
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod query()
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod whereAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionManualPaymentMethod whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperSubscriptionManualPaymentMethod
 */
class SubscriptionManualPaymentMethod extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'bank_name',
        'account_number',
        'account_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
