<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageResponse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'message_id',
        'message',
        'created_by',
        'created_at'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = Carbon::now();
        });
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y H.i');
    }

    /**
     * Get the user that owns the MessageResponse
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
