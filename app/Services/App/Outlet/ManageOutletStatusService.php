<?php

namespace App\Services\App\Outlet;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Models\Outlet;
use App\Models\OutletAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManageOutletStatusService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function toggleStatus(Outlet $outlet, bool $isActive, User $user): Outlet
    {
        if ($isActive) {
            // Find if there is an unpaid invoice for this outlet
            $unpaidInvoice = \App\Models\Invoice::where('business_id', $outlet->business_id)
                ->where('status', '!=', 'paid')
                ->whereHas('items', function ($query) use ($outlet) {
                    $query->where('item_type', 'outlet_addition')
                        ->where('metadata->outlet_id', $outlet->id);
                })
                ->first();

            if ($unpaidInvoice) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'unpaid_invoice_number' => $unpaidInvoice->invoice_number,
                    'unpaid_invoice_url' => route('settings.billing.invoices.show', $unpaidInvoice->invoice_number),
                    'error' => ['Outlet tidak dapat diaktifkan karena ada tagihan penambahan outlet yang belum dibayar.'],
                ]);
            }
        }

        $outlet->is_active = $isActive;
        $outlet->save();

        // Sync with subscription_outlets table
        $subscription = $outlet->business->subscriptions()
            ->where('status', 'active')
            ->first();

        if ($subscription) {
            if ($isActive) {
                $subscription->subscriptionOutlets()->updateOrCreate([
                    'outlet_id' => $outlet->id,
                    'deactivated_at' => null,
                ], [
                    'activated_at' => \Carbon\Carbon::now(),
                ]);
            } else {
                $subscription->subscriptionOutlets()
                    ->where('outlet_id', $outlet->id)
                    ->whereNull('deactivated_at')
                    ->update([
                        'deactivated_at' => \Carbon\Carbon::now(),
                    ]);
            }
        }

        $action = $isActive ? 'enabled' : 'disabled';
        OutletAuditLog::create([
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'action' => $action,
        ]);

        $this->auditLogger->log(
            module: AuditModuleEnum::SETTINGS->value,
            action: 'outlet.status_toggled',
            description: "Mengubah status cabang {$outlet->name} menjadi ".($isActive ? 'Aktif' : 'Nonaktif'),
            subject: $outlet,
            causer: $user,
            businessId: $outlet->business_id,
            outletId: $outlet->id,
            properties: [
                'is_active' => $isActive,
            ]
        );

        return $outlet;
    }

    public function delete(Outlet $outlet, User $user): void
    {
        DB::transaction(function () use ($outlet, $user) {
            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'deleted',
            ]);

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'outlet.deleted',
                description: "Memindahkan cabang outlet ke sampah: {$outlet->name}",
                subject: $outlet,
                causer: $user,
                businessId: $outlet->business_id,
                outletId: $outlet->id
            );

            $outlet->delete();
        });
    }

    public function restore(string $outletId, User $user): Outlet
    {
        return DB::transaction(function () use ($outletId, $user) {
            $outlet = Outlet::withTrashed()->where('id', $outletId)->firstOrFail();
            $outlet->restore();

            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'restored',
            ]);

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'outlet.restored',
                description: "Memulihkan cabang outlet: {$outlet->name}",
                subject: $outlet,
                causer: $user,
                businessId: $outlet->business_id,
                outletId: $outlet->id
            );

            return $outlet;
        });
    }

    public function setMainOutlet(Outlet $outlet, User $user): Outlet
    {
        if (! $outlet->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'error' => ['Hanya outlet yang aktif yang dapat dijadikan sebagai outlet utama.'],
            ]);
        }

        return DB::transaction(function () use ($outlet, $user) {
            Outlet::where('business_id', $outlet->business_id)
                ->where('is_main_outlet', true)
                ->update(['is_main_outlet' => false]);

            $outlet->is_main_outlet = true;
            $outlet->save();

            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'set_as_main',
            ]);

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'outlet.set_main',
                description: "Menjadikan {$outlet->name} sebagai outlet utama",
                subject: $outlet,
                causer: $user,
                businessId: $outlet->business_id,
                outletId: $outlet->id
            );

            return $outlet;
        });
    }
}
