<?php

namespace App\Support\Enums;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Enums\AuditModuleEnum;
use App\Enums\BusinessStatus;
use App\Enums\CustomerGender;
use App\Enums\DatePresetEnum;
use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryCostingMethod;
use App\Enums\InventoryMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\PaymentManualValidationStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\PromoStatus;
use App\Enums\PromoTarget;
use App\Enums\PromoType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseReturnStatus;
use App\Enums\RoleEnum;
use App\Enums\RoleTemplateEnum;
use App\Enums\ShiftCashLogType;
use App\Enums\ShiftStatus;
use App\Enums\StockOpnameStatus;
use App\Enums\StockTransferStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Feature;

class FrontendEnumProvider
{
    /**
     * Cache in-memory hasil serialisasi enum.
     *
     * @var array<string, array<string, mixed>>|null
     */
    protected static ?array $cachedEnums = null;

    /**
     * Daftar Backed Enum yang di-expose ke antarmuka pengguna (Frontend).
     *
     * @var array<class-string>
     */
    protected static array $frontendEnums = [
        AdjustmentReason::class,
        AdjustmentStatus::class,
        AuditModuleEnum::class,
        BusinessStatus::class,
        CustomerGender::class,
        DatePresetEnum::class,
        DeviceTypeEnum::class,
        FeatureEnum::class,
        GoodsReceiptStatus::class,
        InventoryCostingMethod::class,
        InventoryMovementType::class,
        NotificationCategoryEnum::class,
        NotificationScopeEnum::class,
        NotificationTypeEnum::class,
        PaymentMethodType::class,
        PermissionEnum::class,
        PlanEnum::class,
        PromoStatus::class,
        PromoTarget::class,
        PromoType::class,
        PurchaseOrderStatus::class,
        PurchaseReturnStatus::class,
        RoleEnum::class,
        RoleTemplateEnum::class,
        ShiftStatus::class,
        ShiftCashLogType::class,
        StockOpnameStatus::class,
        StockTransferStatus::class,
        TransactionStatus::class,
        TransactionPaymentStatus::class,
        InvoiceStatus::class,
        PaymentManualValidationStatus::class,
        SubscriptionStatus::class,
    ];

    /**
     * Ambil seluruh representasi enum untuk frontend.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        if (static::$cachedEnums !== null) {
            return static::$cachedEnums;
        }

        $result = [];

        foreach (static::$frontendEnums as $enumClass) {
            if (! enum_exists($enumClass)) {
                continue;
            }

            $shortName = class_basename($enumClass);
            $result[$shortName] = static::transform($enumClass);
        }

        return static::$cachedEnums = $result;
    }

    /**
     * Transform Backed Enum class ke representasi array terstruktur.
     *
     * @param  class-string  $enumClass
     * @return array<string, mixed>
     */
    public static function transform(string $enumClass): array
    {
        $data = [];
        $meta = [];
        $options = [];

        // Special handling for FeatureEnum: metadata & grouping come from database
        if ($enumClass === FeatureEnum::class && class_exists(Feature::class)) {
            $features = Feature::getAllCached()->keyBy('code');
            $grouped = [];

            foreach ($enumClass::cases() as $case) {
                $value = $case->value;
                $name = $case->name;

                $data[$name] = $value;
                $upperName = strtoupper($name);
                if ($upperName !== $name) {
                    $data[$upperName] = $value;
                }

                $featureModel = $features->get($value);
                $label = $featureModel?->name ?? $name;
                $description = $featureModel?->description ?? '';
                $groupLabel = $featureModel?->group_label ?? 'Lainnya';

                $meta[$value] = [
                    'label' => $label,
                    'description' => $description,
                    'group' => $featureModel?->group ?? 'other',
                    'group_label' => $groupLabel,
                ];

                $options[] = [
                    'value' => $value,
                    'label' => $label,
                ];

                $grouped[$groupLabel][] = [
                    'value' => $value,
                    'label' => $label,
                    'description' => $description,
                ];
            }

            $data['_meta'] = $meta;
            $data['_options'] = $options;
            $data['_grouped'] = $grouped;

            return $data;
        }

        foreach ($enumClass::cases() as $case) {
            $value = $case->value;
            $name = $case->name;

            // Direct mapping case name ke value (PascalCase & UPPERCASE fallback)
            $data[$name] = $value;
            $upperName = strtoupper($name);
            if ($upperName !== $name) {
                $data[$upperName] = $value;
            }

            $label = method_exists($case, 'label') ? $case->label() : $name;
            $color = method_exists($case, 'color') ? $case->color() : null;

            $meta[$value] = array_filter([
                'label' => $label,
                'color' => $color,
            ], fn ($val) => $val !== null);

            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        $data['_meta'] = $meta;
        $data['_options'] = $options;

        if (method_exists($enumClass, 'grouped')) {
            $data['_grouped'] = $enumClass::grouped();
        }

        return $data;
    }

    /**
     * Reset cache (berguna saat testing).
     */
    public static function clearCache(): void
    {
        static::$cachedEnums = null;
    }
}
