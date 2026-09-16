<?php

namespace Tests\Unit\Services\App\Outlet;

use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Outlet\UpdateOutletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOutletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateOutletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UpdateOutletService;
    }

    protected function createMerchantUser(): User
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        return User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);
    }

    public function test_it_updates_outlet()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Old Outlet Name',
        ]);

        $data = [
            'name' => 'New Outlet Name',
            'phone' => '87654321',
        ];

        $result = $this->service->execute($outlet, $data, $user);

        $this->assertEquals('New Outlet Name', $result->name);
        $this->assertEquals('87654321', $result->phone);

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'action' => 'updated',
        ]);
    }
}
