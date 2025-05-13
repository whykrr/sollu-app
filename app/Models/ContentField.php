<?php

namespace App\Models;

use DateTimeInterface;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContentField extends Model
{
    use HasFactory, HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content_type_id',
        'name',
        'key',
        'field_type',
        'is_required',
        'validation',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_required' => 'boolean',
        'validation' => 'object',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('key')
            ->allowDuplicateSlugs();
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function content_type(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function field_value(): HasOne
    {
        return $this->hasOne(ContentFieldValue::class);
    }
}
