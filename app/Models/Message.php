<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
        'read_at',
        'read_by'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['created_at_full'];

    /**
     * Serialize the given date into a specific string format.
     *
     * @param \DateTimeInterface|Carbon $date The date object to be serialized.
     * @return string The formatted date string.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        if ($date->isToday()) {
            return $date->format('H.i');
        }
        if ($date->year === now()->year) {
            return $date->format('d/m');
        }

        return $date->format('d/m/Y');
    }

    public function response(): HasOne|MessageReply
    {
        return $this->hasOne(MessageResponse::class);
    }

    /**
     * Get the reader that owns the Message
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function reader(): BelongsTo|User
    {
        return $this->belongsTo(User::class, 'id', 'read_by');
    }

    public function getCreatedAtFullAttribute()
    {
        return Carbon::parse($this->created_at)->format('d M Y H.i');
    }

    public function scopeNewest(Builder $builder): Builder
    {
        return $builder->orderBy('created_at', 'desc');
    }

    public function scopeFilters(Builder $builder, array $filters): Builder
    {
        return $builder->when(
            $filters['search'] ?? false,
            fn($builder, $value) => $builder->whereLike('subject', "%$value%")
        )->when(
            $filters['status'] ?? false,
            fn($builder, $value) => $builder->where('status', $value)
        )->when(
            $filters['from'] ?? false,
            fn($builder, $value) => $builder->whereBetween('created_at', [formatStartDate($value), formatEndDate($filters['to'])])
        );
    }
}
