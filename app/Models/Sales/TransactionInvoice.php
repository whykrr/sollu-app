<?php

namespace App\Models\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperTransactionInvoice
 */
class TransactionInvoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'transaction_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'payment_term',
        'status',
        'terms_and_conditions',
        'notes',
        'sent_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
