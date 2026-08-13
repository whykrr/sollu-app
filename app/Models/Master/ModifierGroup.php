<?php

namespace App\Models\Master;

use App\Trait\HasBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModifierGroup extends Model
{
    use HasBusiness;
    use HasUuids;

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn ($builder, $value) => $builder->where('name', 'ilike', "%{$value}%")
        );
    }

    protected $fillable = [
        'business_id',
        'name',
        'selection_type',
        'max_select',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function options()
    {
        return $this->hasMany(ModifierOption::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_modifier_groups');
    }
}
