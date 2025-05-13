<?php

namespace App\Models;

use DateTimeInterface;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContentType extends Model
{
    use HasFactory, HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'is_listed',
        'max_row',
        'title_aliases',
        'with_meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_listed' => 'boolean',
        'with_meta' => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y H.i');
    }


    public function contents(): HasMany|Content
    {
        return $this->hasMany(Content::class);
    }
    public function content(): HasOne|Content
    {
        return $this->HasOne(Content::class);
    }

    public function content_fields(): HasMany|ContentField
    {
        return $this->hasMany(ContentField::class);
    }

    public function localized_contents(): HasMany|LocalizedContent
    {
        return $this->hasMany(LocalizedContent::class);
    }

    public function scopeParent(Builder $builder): Builder
    {
        return $builder->where('is_listed', '=', false)->whereNull('parent_id');
    }

    public function scopeSidebar(Builder $builder): Builder
    {
        return $builder->select(['id', 'parent_id', 'name', 'is_listed'])->whereNull('parent_id');
    }
    public function scopeChildren(Builder $builder, int $parent_id): Builder
    {
        return $builder->where('parent_id', '=', $parent_id);
    }

    public function scopeSlugs(Builder $builder, array $slugs): Builder
    {
        return $builder->whereIn('slug', $slugs);
    }
}
