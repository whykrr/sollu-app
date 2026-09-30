<?php

namespace Tests\Unit\Services\App\Reports;

use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Promotion\Promotion;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionPromo;
use App\Models\User;
use App\Services\App\Reports\PromotionReportService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromotionReportService;
    }

    public function test_it_gets_report(): void
    {
        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Report Merchant',
            'owner_name' => 'Report Owner',
            'email' => 'report_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Report User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Outlet Report',
            'is_active' => true,
        ]);

        $promo = Promotion::create([
            'business_id' => $user->business_id,
            'name' => 'Diskon Merdeka',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 10000,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->addDays(10)->toDateString(),
            'status' => PromotionStatus::Active->value,
            'applies_to_all_outlets' => true,
            'created_by' => $user->id,
        ]);

        $now = Carbon::now();

        $transaction = Transaction::create([
            'outlet_id' => $outlet->id,
            'status' => 'completed',
            'subtotal' => 100000,
            'total' => 90000,
            'transaction_number' => 'TRX-101',
            'created_at' => $now,
        ]);

        TransactionPromo::create([
            'transaction_id' => $transaction->id,
            'promo_id' => $promo->id,
            'promo_name' => 'Diskon Merdeka',
            'discount_type' => 'fixed',
            'discount_amount' => 10000,
            'discount_value' => 10000,
        ]);

        // Other business promo & transaction to assert isolation
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '081234567891',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $otherOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Outlet',
            'is_active' => true,
        ]);

        $otherPromo = Promotion::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Diskon Lain',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Fixed->value,
            'discount_value' => 50000,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->addDays(10)->toDateString(),
            'status' => PromotionStatus::Active->value,
            'applies_to_all_outlets' => true,
            'created_by' => $user->id,
        ]);

        $otherTx = Transaction::create([
            'outlet_id' => $otherOutlet->id,
            'status' => 'completed',
            'subtotal' => 200000,
            'total' => 150000,
            'transaction_number' => 'TRX-OTHER-1',
            'created_at' => $now,
        ]);

        TransactionPromo::create([
            'transaction_id' => $otherTx->id,
            'promo_id' => $otherPromo->id,
            'promo_name' => 'Diskon Lain',
            'discount_type' => 'fixed',
            'discount_amount' => 50000,
            'discount_value' => 50000,
        ]);

        $startDate = $now->copy()->subDay();
        $endDate = $now->copy()->addDay();

        $result = $this->service->getReport($business->id, [$outlet->id], $startDate, $endDate);

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('promotions', $result);

        $summary = $result['summary'];
        $this->assertEquals(1, $summary['total_usage']);
        $this->assertEquals(10000, $summary['total_discount_given']);
        $this->assertEquals(1, $summary['total_active_promos']);

        $promotions = $result['promotions'];
        $this->assertNotEmpty($promotions->items());
        $firstItem = $promotions->items()[0];

        $this->assertEquals('Diskon Merdeka', $firstItem->promo_name);
        $this->assertEquals('fixed', $firstItem->promo_type);
        $this->assertEquals(1, $firstItem->total_usage);
        $this->assertEquals(10000, $firstItem->total_discount_given);
    }
}
