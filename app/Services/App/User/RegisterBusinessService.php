<?php

namespace App\Services\App\User;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\App\Outlet\OutletProvisioningService;
use App\Services\App\Role\RoleProvisioningService;
use Illuminate\Support\Facades\DB;

class RegisterBusinessService
{
    public function __construct(
        protected OutletProvisioningService $provisioningService
    ) {}

    /**
     * Register a new business, default main outlet, and owner user.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, business: Business, outlet: Outlet}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $type = BusinessType::find($data['business_type_id']);

            $business = Business::create([
                'name' => $data['name'],
                'owner_name' => $data['owner_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'status' => 'active',
                'business_type_id' => $data['business_type_id'],
                'settings' => $type?->default_settings ?? null,
                'trial_end_at' => now()->addDays(SystemSetting::getTrialDurationDays()),
            ]);

            $outlet = $business->outlets()->create([
                'name' => $data['outlet_name'],
                'is_main_outlet' => true,
            ]);

            /** @var User $user */
            $user = $business->users()->create([
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'is_root_user' => true,
            ]);

            // Provision roles for this business
            app(RoleProvisioningService::class)->provision($business);

            // Attach root user to main outlet (Root user has wildcard * and no role)
            $user->outlets()->attach($outlet->id);

            // Provision default settings & payment methods
            $this->provisioningService->provisionAll($outlet);

            return [
                'user' => $user,
                'business' => $business,
                'outlet' => $outlet,
            ];
        });
    }
}
