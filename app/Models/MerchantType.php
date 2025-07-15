<?php

namespace App\Models;

use App\Casts\SettingsCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'default_settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_settings' => SettingsCast::class,
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
