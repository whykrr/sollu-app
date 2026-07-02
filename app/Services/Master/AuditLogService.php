<?php

namespace App\Services\Master;

use Illuminate\Support\Facades\DB;

class AuditLogService
{
    public function log(string $businessId, string $entityType, string $entityId, string $action, array $before = null, array $after = null)
    {
        DB::table('audit_logs')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'business_id' => $businessId,
            'actor_type' => 'App\Models\User',
            'actor_id' => auth()->id() ?? \Illuminate\Support\Str::uuid(), // fallback for testing
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'before_value' => $before ? json_encode($before) : null,
            'after_value' => $after ? json_encode($after) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
