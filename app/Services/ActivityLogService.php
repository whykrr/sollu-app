<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityLogService
{
    /**
     * Log an activity.
     *
     * @param  Model  $subject
     * @param  Model|TModel|null  $causer
     */
    public function log($subject, string $action, ?Model $causer = null, array $properties = []): void
    {
        DB::table('activity_logs')->insert([
            'id' => Str::uuid()->toString(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'causer_type' => $causer ? $causer->getMorphClass() : null,
            'causer_id' => $causer ? $causer->getKey() : null,
            'action' => $action,
            'properties' => empty($properties) ? null : json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
