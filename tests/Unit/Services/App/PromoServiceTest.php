<?php

namespace Tests\Unit\Services\App;

use App\Enums\PromoStatus;
use App\Enums\PromoTarget;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Promo;
use App\Models\User;
use App\Services\App\Master\ActivityLogService;
use App\Services\App\Promotion\PromoService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class PromoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PromoService $service;

    protected $activityLogServiceMock;

    protected Business $business;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        /** @var Business $business */
        $business = Business::create([
            'name' => 'Test Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);
        $this->business = $business;

        /** @var User $user */
        $user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Test User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);
        $this->user = $user;

        $this->actingAs($this->user);

        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);
        $this->service = new PromoService($this->activityLogServiceMock);
    }

    public function test_it_creates_promo_with_relations(): void
    {
        $outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet 1',
        ]);

        $item = ProductItem::create([
            'business_id' => $this->business->id,
            'name' => 'Product 1',
            'item_type' => 'raw_material',
        ]);

        $data = [
            'business_id' => $this->business->id,
            'name' => 'Promo Lebaran',
            'promo_type' => 'fixed',
            'target_type' => PromoTarget::Product->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(7),
            'applies_to_all_outlets' => false,
            'outlet_ids' => [$outlet->id],
            'inventory_item_ids' => [$item->id],
        ];

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Promo::class), 'created', $this->user);

        $promo = $this->service->create($data, $this->user);

        $this->assertInstanceOf(Promo::class, $promo);
        $this->assertEquals('Promo Lebaran', $promo->name);
        $this->assertEquals(PromoStatus::Draft, $promo->status);
        $this->assertEquals($this->user->id, $promo->created_by);

        $this->assertCount(1, $promo->outlets);
        $this->assertEquals($outlet->id, $promo->outlets->first()->id);

        $this->assertCount(1, $promo->inventoryItems);
        $this->assertEquals($item->id, $promo->inventoryItems->first()->id);
    }

    public function test_it_does_not_sync_relations_from_other_business(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Business',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08777777777',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $otherOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Outlet',
        ]);

        $data = [
            'business_id' => $this->business->id,
            'name' => 'Promo Isolation Test',
            'promo_type' => 'fixed',
            'target_type' => PromoTarget::Bill->value,
            'discount_value' => 5000,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(7),
            'applies_to_all_outlets' => false,
            'outlet_ids' => [$otherOutlet->id],
        ];

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Promo::class), 'created', $this->user);

        $promo = $this->service->create($data, $this->user);

        // The other business outlet must NOT be synced
        $this->assertCount(0, $promo->outlets);
    }

    public function test_it_updates_draft_promo(): void
    {
        /** @var Promo $promo */
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Old Promo',
            'promo_type' => 'fixed',
            'target_type' => 'bill',
            'discount_value' => 10000,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(7),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Promo::class), 'updated', null);

        $updated = $this->service->update($promo, ['name' => 'New Promo']);

        $this->assertEquals('New Promo', $updated->name);
    }

    public function test_it_cannot_update_non_draft_promo(): void
    {
        /** @var Promo $promo */
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Old Promo',
            'promo_type' => 'fixed',
            'target_type' => 'bill',
            'discount_value' => 10000,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(7),
            'status' => PromoStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hanya promo berstatus Draf yang dapat diubah.');

        $this->service->update($promo, ['name' => 'New Promo']);
    }

    public function test_it_deletes_draft_promo(): void
    {
        /** @var Promo $promo */
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Old Promo',
            'promo_type' => 'fixed',
            'target_type' => 'bill',
            'discount_value' => 10000,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(7),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Promo::class), 'deleted', null);

        $this->service->delete($promo);

        $this->assertDatabaseMissing('promos', ['id' => $promo->id]);
    }

    public function test_it_cannot_delete_active_promo(): void
    {
        /** @var Promo $promo */
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Old Promo',
            'promo_type' => 'fixed',
            'target_type' => 'bill',
            'discount_value' => 10000,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(7),
            'status' => PromoStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Promo yang sudah aktif tidak dapat dihapus.');

        $this->service->delete($promo);
    }

    public function test_it_publishes_promo(): void
    {
        /** @var Promo $promo */
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Old Promo',
            'promo_type' => 'fixed',
            'target_type' => 'bill',
            'discount_value' => 10000,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(7),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Promo::class), 'published', $this->user);

        $published = $this->service->publish($promo, $this->user);

        $this->assertEquals(PromoStatus::Active, $published->status);
        $this->assertEquals($this->user->id, $published->published_by);
        $this->assertNotNull($published->published_at);
    }

    public function test_it_cannot_publish_past_promo(): void
    {
        /** @var Promo $promo */
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Old Promo',
            'promo_type' => 'fixed',
            'target_type' => 'bill',
            'discount_value' => 10000,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->subDays(2),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tanggal berakhir promo sudah terlewat.');

        $this->service->publish($promo, $this->user);
    }

    public function test_it_unpublishes_promo(): void
    {
        /** @var Promo $promo */
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Old Promo',
            'promo_type' => 'fixed',
            'target_type' => 'bill',
            'discount_value' => 10000,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(7),
            'status' => PromoStatus::Active->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Promo::class), 'unpublished', $this->user);

        $unpublished = $this->service->unpublish($promo, $this->user);

        $this->assertEquals(PromoStatus::Inactive, $unpublished->status);
    }

    public function test_it_cannot_unpublish_non_active_promo(): void
    {
        /** @var Promo $promo */
        $promo = Promo::create([
            'business_id' => $this->business->id,
            'name' => 'Old Promo',
            'promo_type' => 'fixed',
            'target_type' => 'bill',
            'discount_value' => 10000,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(7),
            'status' => PromoStatus::Draft->value,
            'created_by' => $this->user->id,
            'applies_to_all_outlets' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hanya promo aktif yang dapat dinonaktifkan.');

        $this->service->unpublish($promo, $this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
