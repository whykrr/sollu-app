<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use DateTimeInterface;

class ContentFieldValue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content_id',
        'content_field_id',
        'value',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['src'];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }


    public function content_field(): BelongsTo|ContentField
    {
        return $this->belongsTo(ContentField::class);
    }

    public function getSrcAttribute()
    {
        if (strpos($this->value, 'images/') !== false || strpos($this->value, 'files/') !== false) {
            return asset("storage/{$this->value}");
        }
        return null;
    }
}
