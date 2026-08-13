<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModifierOption extends Model
{
    use HasUuids;

    protected $fillable = [
        'modifier_group_id',
        'name',
        'additional_price',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'additional_price' => 'float',
    ];

    public function modifierGroup()
    {
        return $this->belongsTo(ModifierGroup::class);
    }

    public function recipeItems()
    {
        return $this->hasMany(ModifierRecipeItem::class);
    }
}
