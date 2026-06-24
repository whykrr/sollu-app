<?php

namespace App\Services\Outlet;

use App\Helpers\SummaryUser;
use App\Models\Outlet;
use App\Models\User;
use App\Models\OutletAuditLog;
use Illuminate\Support\Facades\DB;

class ManageOutletStatusService
{
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

        SummaryUser::cacheDelete();

        $action = $isActive ? 'enabled' : 'disabled';
        OutletAuditLog::create([
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'action' => $action,
        ]);

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
            $outlet->delete();
            SummaryUser::cacheDelete();
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
            SummaryUser::cacheDelete();

            return $outlet;
        });
    }
}
