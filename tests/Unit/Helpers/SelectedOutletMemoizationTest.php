<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SelectedOutlet;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SelectedOutletMemoizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        SelectedOutlet::flushMemoization();
    }

    protected function createMerchantWithOutlets(int $outletCount = 2): array
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Memo Business',
            'owner_name' => 'Memo Owner',
            'email' => 'memo_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Memo User',
            'email' => 'memo_user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $outlets = [];
        for ($i = 1; $i <= $outletCount; $i++) {
            $outlet = Outlet::create([
                'business_id' => $business->id,
                'name' => "Outlet {$i}",
                'slug' => "outlet-{$i}-".uniqid(),
                'is_active' => true,
            ]);
            $user->outlets()->attach($outlet->id);
            $outlets[] = $outlet;
        }

        return [$user, $business, $outlets];
    }

    public function test_selected_outlet_is_memoized_in_request_lifecycle(): void
    {
        [$user, $business, $outlets] = $this->createMerchantWithOutlets(2);

        // Put selected outlet in session
        session()->put(SelectedOutlet::make($user)->getSessionKey(), $outlets[0]->id);

        // First call
        $queriesBefore = 0;
        DB::listen(function () use (&$queriesBefore) {
            $queriesBefore++;
        });

        $res1 = SelectedOutlet::make($user)->get();
        $this->assertNotNull($res1);
        $this->assertEquals($outlets[0]->id, $res1->id);

        $firstQueryCount = $queriesBefore;

        // Second and third calls in the same request lifecycle should not execute extra queries
        $res2 = SelectedOutlet::make($user)->get();
        $cached = SelectedOutlet::make($user)->cached();

        $this->assertEquals($outlets[0]->id, $res2->id);
        $this->assertEquals($outlets[0]->id, $cached['id']);
        $this->assertEquals($firstQueryCount, $queriesBefore, 'Extra SQL queries were executed instead of using memoized instance.');
    }

    public function test_flush_memoization_clears_in_memory_cache(): void
    {
        [$user, $business, $outlets] = $this->createMerchantWithOutlets(2);

        session()->put(SelectedOutlet::make($user)->getSessionKey(), $outlets[0]->id);
        $res1 = SelectedOutlet::make($user)->get();
        $this->assertEquals($outlets[0]->id, $res1->id);

        // Change session and flush
        SelectedOutlet::make($user)->change($outlets[1]->id);

        $res2 = SelectedOutlet::make($user)->get();
        $this->assertEquals($outlets[1]->id, $res2->id);
    }
}
