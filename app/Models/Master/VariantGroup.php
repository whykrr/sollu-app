<?php

namespace App\Models\Master;

use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperVariantGroup
 */
class VariantGroup extends Model
{
    use HasUuids, SortableModel;

    protected $fillable = [
        'product_id',
        'name',
        'sort_order',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function options()
    {
        return $this->hasMany(VariantGroupOption::class)->orderBy('sort_order');
    }
}
