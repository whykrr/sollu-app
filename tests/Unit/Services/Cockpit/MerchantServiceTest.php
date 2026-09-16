<?php

namespace Tests\Unit\Services\Cockpit;

use App\Enums\BusinessStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Cockpit\MerchantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MerchantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MerchantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MerchantService;
    }

    public function test_get_paginated_merchants_with_filters(): void
    {
        $type = BusinessType::create([
            'code' => 'retail',
            'name' => 'Minimarket',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $b1 = Business::create([
            'name' => 'Sollu Mart Jakarta',
            'owner_name' => 'Budi',
            'email' => 'budi@sollumart.test',
            'phone' => '081111111111',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $b2 = Business::create([
            'name' => 'Toko Kelontong Bandung',
            'owner_name' => 'Asep',
            'email' => 'asep@bandung.test',
            'phone' => '082222222222',
            'status' => BusinessStatus::Suspended,
            'trial_end_at' => now()->subDays(5),
            'business_type_id' => $type->id,
        ]);

        // Search test
        $resultSearch = $this->service->getPaginatedMerchants(['search' => 'Jakarta'], 10);
        $this->assertSame(1, $resultSearch->total());
        $this->assertSame('Sollu Mart Jakarta', $resultSearch->first()->name);

        // Status test
        $resultStatus = $this->service->getPaginatedMerchants(['status' => 'suspended'], 10);
        $this->assertSame(1, $resultStatus->total());
        $this->assertSame('Toko Kelontong Bandung', $resultStatus->first()->name);
    }

    public function test_get_metrics_calculates_correct_counts(): void
    {
        Cache::forget('cockpit:merchants:metrics');

        $type = BusinessType::create([
            'code' => 'cafe',
            'name' => 'Cafe Shop',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $b1 = Business::create([
            'name' => 'Kopi Mantap',
            'owner_name' => 'Rina',
            'email' => 'rina@kopi.test',
            'phone' => '081333333333',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(7),
            'business_type_id' => $type->id,
        ]);

        Outlet::create([
            'business_id' => $b1->id,
            'name' => 'Outlet 1',
            'is_active' => true,
        ]);

        Outlet::create([
            'business_id' => $b1->id,
            'name' => 'Outlet 2',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'code' => 'basic_plan_test',
            'name' => 'Paket Basic',
            'price_per_outlet' => 50000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
            'is_public' => true,
        ]);

        Subscription::create([
            'business_id' => $b1->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'started_at' => now(),
            'expired_at' => now()->addMonth(),
        ]);

        $metrics = $this->service->getMetrics();

        $this->assertSame(1, $metrics['total_merchants']);
        $this->assertSame(1, $metrics['active_merchants']);
        $this->assertSame(0, $metrics['suspended_merchants']);
        $this->assertSame(1, $metrics['active_subscribers']);
        $this->assertSame(1, $metrics['trial_merchants']);
        $this->assertSame(2, $metrics['total_outlets']);
    }

    public function test_get_merchant_detail_returns_structured_payload(): void
    {
        $type = BusinessType::create([
            'code' => 'resto',
            'name' => 'Resto & Cafe',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Dapoer Nusantara',
            'owner_name' => 'Chef Juna',
            'email' => 'juna@dapoer.test',
            'phone' => '081444444444',
            'address' => 'Jl. Kuliner No. 1',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        Outlet::create([
            'business_id' => $business->id,
            'name' => 'Dapoer Pusat',
            'address' => 'Jl. Kuliner No. 1',
            'is_active' => true,
            'is_main_outlet' => true,
        ]);

        User::create([
            'business_id' => $business->id,
            'name' => 'Chef Juna',
            'email' => 'juna@dapoer.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $detail = $this->service->getMerchantDetail($business->id);

        $this->assertSame($business->id, $detail['id']);
        $this->assertSame('Dapoer Nusantara', $detail['name']);
        $this->assertSame(1, $detail['outlets_count']);
        $this->assertSame(1, $detail['users_count']);
        $this->assertCount(1, $detail['outlets']);
        $this->assertCount(1, $detail['users']);
        $this->assertSame('trial', $detail['active_plan']['type']);
        $this->assertSame('Masa Uji Coba (Trial)', $detail['active_plan']['plan_name']);
    }

    public function test_toggle_status_updates_database_and_creates_log(): void
    {
        $type = BusinessType::create([
            'code' => 'barber',
            'name' => 'Barbershop',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Pangkas Rambut Gaul',
            'owner_name' => 'Andi',
            'email' => 'andi@gaul.test',
            'phone' => '081555555555',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $adminId = '00000000-0000-0000-0000-000000000001';
        $updated = $this->service->toggleStatus($business->id, 'suspended', $adminId);

        $this->assertEquals(BusinessStatus::Suspended, $updated->status);
        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'status' => 'suspended',
        ]);

        $this->assertDatabaseHas('business_status_logs', [
            'business_id' => $business->id,
            'old_status' => 'active',
            'new_status' => 'suspended',
            'changed_by' => $adminId,
        ]);
    }

    public function test_generate_impersonation_token(): void
    {
        $type = BusinessType::create([
            'code' => 'gym',
            'name' => 'Fitness & Gym',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Mega Fitness',
            'owner_name' => 'Ade Rai',
            'email' => 'ade@fitness.test',
            'phone' => '081666666666',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Ade Rai',
            'email' => 'ade@fitness.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $adminId = '00000000-0000-0000-0000-000000000001';
        $token = $this->service->generateImpersonationToken($business->id, $user->id, $adminId);

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));

        $cached = Cache::get("impersonate:token:{$token}");
        $this->assertNotNull($cached);
        $this->assertSame($user->id, $cached['user_id']);
        $this->assertSame($business->id, $cached['business_id']);
        $this->assertSame($adminId, $cached['admin_id']);
    }
}
