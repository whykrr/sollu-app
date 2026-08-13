<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperUom
 */
class Uom extends Model
{
    use HasFactory;
    use HasUuids;

    protected $guarded = [];

    public function code(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucwords(strtolower($value)),
            set: fn ($value) => ucwords(strtolower($value))
        );
    }
}
