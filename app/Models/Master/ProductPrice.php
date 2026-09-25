<?php

namespace App\Models\Master;

use App\Models\Outlet;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperProductPrice
 */
class ProductPrice extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id',
        'outlet_id',
        'product_item_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }
}
