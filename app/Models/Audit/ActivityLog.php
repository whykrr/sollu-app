<?php

namespace App\Models\Audit;

use App\Models\Business;
use App\Models\Outlet;
use App\Trait\SortableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * @mixin IdeHelperActivityLog
 */
class ActivityLog extends Model
{
    use HasFactory;
    use HasUuids;
    use SortableModel;

    protected $fillable = [
        'id',
        'business_id',
        'outlet_id',
        'causer_type',
        'causer_id',
        'subject_type',
        'subject_id',
        'module',
        'action',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
        'updated_at',
    ];

    /**
     * Whitelist kolom untuk pengurutan tabel.
     *
     * @var array<int, string>
     */
    protected array $sortable = [
        'created_at',
        'module',
        'action',
    ];

    /**
     * Resolusi nama tabel berdasarkan database driver.
     */
    public function getTable(): string
    {
        if (DB::getDriverName() === 'sqlite' || config('database.default') === 'sqlite') {
            return 'activity_logs';
        }

        return 'audit.activity_logs';
    }

    /**
     * Tipe data casting.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForBusiness(Builder $query, string $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForOutlet(Builder $query, ?string $outletId): Builder
    {
        if (! $outletId) {
            return $query;
        }

        return $query->where('outlet_id', $outletId);
    }

    public function scopeForModule(Builder $query, ?string $module): Builder
    {
        if (! $module) {
            return $query;
        }

        return $query->where('module', $module);
    }
}
