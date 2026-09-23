<?php

namespace App\Services\App\Master;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use Illuminate\Support\Facades\Auth;

/**
 * @deprecated Gunakan App\Contracts\Audit\ActivityLoggerInterface langsung.
 */
class AuditLogService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function log(string $businessId, string $entityType, string $entityId, string $action, ?array $before = null, ?array $after = null): void
    {
        $module = $this->inferModule($entityType);
        $humanAction = str_replace('_', ' ', $action);
        $humanEntity = str_replace('_', ' ', $entityType);
        $description = ucfirst($humanAction)." pada {$humanEntity}";

        $properties = [];
        if ($before !== null) {
            $properties['old'] = $before;
        }
        if ($after !== null) {
            $properties['new'] = $after;
        }

        $user = Auth::user();
        $outletId = session('current_outlet_id') ?? $user?->current_outlet_id;

        $this->auditLogger->log(
            module: $module,
            action: "{$entityType}.{$action}",
            description: $description,
            subject: null,
            causer: $user,
            businessId: $businessId,
            outletId: $outletId,
            properties: $properties
        );
    }

    protected function inferModule(string $entityType): string
    {
        return match ($entityType) {
            'product', 'category', 'modifier_group', 'recipe' => AuditModuleEnum::PRODUCTS->value,
            'payment_method', 'outlet_payment_method', 'business', 'tax', 'receipt', 'device' => AuditModuleEnum::SETTINGS->value,
            'user', 'employee', 'role' => AuditModuleEnum::EMPLOYEES->value,
            'customer', 'promo' => AuditModuleEnum::PROMOTIONS->value,
            'inventory', 'stock', 'purchase_order', 'goods_receipt' => AuditModuleEnum::INVENTORY->value,
            'transaction', 'shift', 'invoice' => AuditModuleEnum::POS->value,
            default => AuditModuleEnum::SETTINGS->value,
        };
    }
}
