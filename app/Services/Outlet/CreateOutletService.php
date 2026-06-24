<?php

namespace App\Services\Outlet;

use App\Helpers\SummaryUser;
use App\Models\Outlet;
use App\Models\OutletAuditLog;
use App\Models\User;
use App\Services\BillingEngine;
use Illuminate\Support\Facades\DB;

class CreateOutletService
{
    public function __construct(protected BillingEngine $billingEngine) {}

    public function execute(array $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user) {
            $outlet                = new Outlet();
            $outlet->business_id   = $user->business_id;
            $outlet->name          = $data['name'];
            $outlet->address       = $data['address']       ?? null;
            $outlet->phone         = $data['phone']         ?? null;
            $outlet->email         = $data['email']         ?? null;
            $outlet->timezone      = $data['timezone']      ?? 'Asia/Jakarta';
            $outlet->currency_code = $data['currency_code'] ?? 'IDR';
            $outlet->is_active     = false;
            $outlet->save();

            // Assign to current user if root, or find root user
            if ($user->is_root_user) {
                $user->outlets()->attach($outlet->id);
            } else {
                $root_user = User::currentBusiness()->where('is_root_user', true)->first();
                if ($root_user) {
                    $root_user->outlets()->attach($outlet->id);
                }
            }

            // Audit log
            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id'   => $user->id,
                'action'    => 'created',
                'metadata'  => ['data' => $data],
            ]);

            SummaryUser::cacheDelete();

            // Generate prorated invoice if business is subscribed to an active plan
            $invoice = null;
            $subscription = $user->business->subscriptions()
                ->where('status', 'active')
                ->first();

            if ($subscription && $subscription->plan) {
                $invoice = $this->billingEngine->generateOutletProratedInvoice($user->business, $subscription, $outlet);
            }

            return [
                'outlet' => $outlet,
                'invoice' => $invoice,
            ];
        });
    }
}
