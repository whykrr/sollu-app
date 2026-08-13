<?php

namespace App\Models\Sales;

use App\Models\Master\ModifierOption;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItemModifier extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'transaction_item_id',
        'modifier_option_id',
        'modifier_name',
        'price',
        'qty',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'qty' => 'float',
        ];
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class);
    }

    public function modifierOption(): BelongsTo
    {
        return $this->belongsTo(ModifierOption::class);
    }
}
