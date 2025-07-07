<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'settings',
    ];

    /**
    * Get the attributes that should be cast.
    *
     * @return array<string, string>
    */
    protected function casts(): array
    {
        return [
            'settings' => 'object',
        ];
    }

    /**
     * Get all of the merchants for the Industry
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

}
