<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalizedContentFieldValue extends Model
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
        'language_id',
        'value',
    ];

    public function content_field(): BelongsTo|ContentField
    {
        return $this->belongsTo(ContentField::class);
    }

    public function language(): BelongsTo|Language
    {
        return $this->belongsTo(Language::class);
    }
}
