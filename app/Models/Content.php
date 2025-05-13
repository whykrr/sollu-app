<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Content extends Model
{
    use HasFactory, HasSlug;

    protected $prefix_slug = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content_type_id',
        'title',
        'slug',
        'published',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'created_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'published' => 'boolean',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y H.i');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function content_type(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function field_values(): HasMany
    {
        return $this->hasMany(ContentFieldValue::class);
    }

    public function scopeNewest(Builder $builder): Builder
    {
        return $builder->orderBy('created_at', 'desc');
    }
}
