<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RecipeVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id',
        'version_number',
        'is_active',
        'effective_from',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items()
    {
        return $this->hasMany(ProductRecipeItem::class);
    }
}
