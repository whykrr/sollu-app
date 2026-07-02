<?php

namespace App\Models\Master;

use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VariantGroupOption extends Model
{
    use HasUuids, SortableModel;

    protected $fillable = [
        'variant_group_id',
        'name',
        'sort_order',
    ];

    public function variantGroup()
    {
        return $this->belongsTo(VariantGroup::class);
    }
}
