<?php

namespace App\Services\App\Outlet;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Models\Outlet;
use App\Models\OutletAuditLog;
use App\Models\User;
use App\Services\App\Subscription\BillingEngine;
use Illuminate\Support\Facades\DB;

class CreateOutletService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        protected BillingEngine $billingEngine,
        protected OutletProvisioningService $provisioningService,
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function execute(array $data, User $user)
    {
        return DB::transaction(function () use ($data, $user) {
            $outlet = new Outlet;
            $outlet->business_id = $user->business_id;
            $outlet->name = $data['name'];
            $outlet->address = $data['address'] ?? null;
            $outlet->phone = $data['phone'] ?? null;
            $outlet->email = $data['email'] ?? null;
            $outlet->timezone = $data['timezone'] ?? 'Asia/Jakarta';
            $outlet->currency_code = $data['currency_code'] ?? 'IDR';
            $outlet->is_active = false;
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

            // Provision default settings & payment methods
            $this->provisioningService->provisionAll($outlet);

            // Audit log
            OutletAuditLog::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => 'created',
                'metadata' => ['data' => $data],
            ]);

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'outlet.created',
                description: "Menambahkan cabang outlet baru: {$outlet->name}",
                subject: $outlet,
                causer: $user,
                businessId: $user->business_id,
                outletId: $outlet->id,
                properties: [
                    'new' => [
                        'name' => $outlet->name,
                        'address' => $outlet->address,
                        'phone' => $outlet->phone,
                    ],
                ]
            );

            // Generate prorated invoice if business is subscribed to an active plan
            $invoice = null;
            $subscription = $user->business->subscriptions()
                ->where('status', 'active')
                ->first();

            if ($subscription && $subscription->plan) {
                $invoice = $this->billingEngine->generateOutletProratedInvoice($user->business, $subscription, $outlet);

                if ($invoice && isset($data['payment_method']) && $data['payment_method'] === 'manual') {
                    $invoice->payments()->create([
                        'amount' => $invoice->total_amount,
                        'payment_method' => 'manual',
                        'status' => 'pending',
                        'payment_reference' => "{$invoice->invoice_number}-MANUAL-".\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)),
                    ]);
                }
            }

            return [
                'outlet' => $outlet,
                'invoice' => $invoice,
            ];
        });
    }
}
