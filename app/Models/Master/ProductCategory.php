<?php

namespace App\Models\Master;

use App\Trait\HasBusiness;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasUuids, HasBusiness, SortableModel, SoftDeletes;

    protected $fillable = [
        'business_id',
        'parent_id',
        'name',
        'sort_order',
    ];

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
