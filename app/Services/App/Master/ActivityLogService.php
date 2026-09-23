<?php

namespace App\Services\App\Master;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Gunakan App\Contracts\Audit\ActivityLoggerInterface langsung.
 */
class ActivityLogService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    /**
     * Log an activity (backward compatible wrapper).
     *
     * @param  Model  $subject
     * @param  array<string, mixed>  $properties
     */
    public function log($subject, string $action, ?Model $causer = null, array $properties = []): void
    {
        $module = $this->inferModule($subject);
        $subjectName = method_exists($subject, 'getName') ? $subject->getName() : ($subject->name ?? class_basename($subject));
        $description = ucfirst($action)." pada {$subjectName}";

        $this->auditLogger->log(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            causer: $causer,
            properties: $properties
        );
    }

    protected function inferModule(Model $subject): string
    {
        $class = get_class($subject);

        if (str_contains($class, 'Inventory') || str_contains($class, 'Stock') || str_contains($class, 'Purchase') || str_contains($class, 'GoodsReceipt')) {
            return AuditModuleEnum::INVENTORY->value;
        }

        if (str_contains($class, 'Product') || str_contains($class, 'Category') || str_contains($class, 'Modifier')) {
            return AuditModuleEnum::PRODUCTS->value;
        }

        if (str_contains($class, 'Promo') || str_contains($class, 'Customer')) {
            return AuditModuleEnum::PROMOTIONS->value;
        }

        if (str_contains($class, 'Transaction') || str_contains($class, 'Shift') || str_contains($class, 'Invoice')) {
            return AuditModuleEnum::POS->value;
        }

        if (str_contains($class, 'User') || str_contains($class, 'Role') || str_contains($class, 'Employee')) {
            return AuditModuleEnum::EMPLOYEES->value;
        }

        return AuditModuleEnum::SETTINGS->value;
    }
}
