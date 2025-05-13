<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class LocalizedContent extends Model
{
    use HasFactory, HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content_type_id',
        'language_id',
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keyword',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function language(): HasOne|Language
    {
        return $this->hasOne(Language::class);
    }
}
