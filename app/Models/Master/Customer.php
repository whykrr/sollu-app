<?php

namespace App\Models\Master;

use App\Enums\CustomerGender;
use App\Models\Business;
use App\Models\Sales\Transaction;
use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperCustomer
 */
class Customer extends Model
{
    use HasBusiness;
    use HasFactory;
    use HasUuids;
    use SortableModel;

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'address',
        'birthdate',
        'gender',
        'notes',
        'is_active',
        'created_by',
    ];

    /**
     * Whitelist column names for sorting.
     */
    protected array $sortable = [
        'name',
        'phone',
        'email',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Cast attributes to native types.
     */
    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'gender' => CustomerGender::class,
            'is_active' => 'boolean',
            'created_by' => 'string',
        ];
    }
}
