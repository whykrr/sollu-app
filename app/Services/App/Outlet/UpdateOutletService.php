<?php

namespace App\Services\App\Outlet;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Models\Outlet;
use App\Models\OutletAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOutletService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function execute(Outlet $outlet, array $data, User $user): Outlet
    {
        return DB::transaction(function () use ($outlet, $data, $user) {
            $oldData = $outlet->toArray();

            $outlet->update($data);

            // Audit log
            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'updated',
                'metadata' => ['old' => $oldData, 'new' => $data],
            ]);

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'outlet.updated',
                description: "Memperbarui informasi cabang outlet: {$outlet->name}",
                subject: $outlet,
                causer: $user,
                businessId: $user->business_id,
                outletId: $outlet->id,
                properties: [
                    'old' => $oldData,
                    'new' => $data,
                ]
            );

            return $outlet;
        });
    }
}
