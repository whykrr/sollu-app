<?php

namespace Tests\Unit\Services\App;

use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
use App\Models\Promotion\Promotion;
use App\Models\User;
use App\Services\App\Master\ActivityLogService;
use App\Services\App\Promotion\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromotionService(new ActivityLogService);
    }

    protected function createBusiness(): Business
    {
        $businessType = BusinessType::first() ?? BusinessType::create([
            'code' => 'retail',
            'name' => 'Retail',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        return Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Test Owner',
            'email' => 'test@example.com',
            'phone' => '08123456789',
            'business_type_id' => $businessType->id,
            'trial_end_at' => now()->addDays(14),
        ]);
    }

    public function test_it_creates_draft_promotion_and_syncs_relations(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);
        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Cabang',
            'code' => 'OUT-CB',
        ]);
        $category = ProductCategory::create([
            'business_id' => $business->id,
            'name' => 'Makanan',
            'created_by' => $user->id,
        ]);

        $data = [
            'business_id' => $business->id,
            'name' => 'Diskon Makanan 15%',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Category->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 15.0,
            'applies_to_all_outlets' => false,
            'outlet_ids' => [$outlet->id],
            'category_ids' => [$category->id],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ];

        $promotion = $this->service->create($data, $user);

        $this->assertInstanceOf(Promotion::class, $promotion);
        $this->assertSame(PromotionStatus::Draft, $promotion->status);
        $this->assertCount(1, $promotion->outlets);
        $this->assertCount(1, $promotion->categories);
    }

    public function test_it_updates_draft_promotion(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        $promotion = Promotion::factory()->create([
            'business_id' => $business->id,
            'name' => 'Nama Awal',
            'discount_value' => 10,
            'created_by' => $user->id,
        ]);

        $updated = $this->service->update($promotion, [
            'name' => 'Nama Baru Diskon 20%',
            'discount_value' => 20,
        ], $user);

        $this->assertSame('Nama Baru Diskon 20%', $updated->name);
        $this->assertSame(20.0, $updated->discount_value);
    }

    public function test_it_fails_to_update_active_promotion_directly(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        $promotion = Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'created_by' => $user->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Promo yang sedang aktif tidak dapat diubah langsung');

        $this->service->update($promotion, ['name' => 'Hacked Name'], $user);
    }

    public function test_it_deletes_draft_promotion_only(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        $draftPromo = Promotion::factory()->create([
            'business_id' => $business->id,
            'created_by' => $user->id,
        ]);

        $this->service->delete($draftPromo, $user);
        $this->assertDatabaseMissing('promotions', ['id' => $draftPromo->id]);

        $activePromo = Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'created_by' => $user->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hanya promo berstatus Draf yang dapat dihapus');

        $this->service->delete($activePromo, $user);
    }

    public function test_it_publishes_and_unpublishes_promotion(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        $promo = Promotion::factory()->create([
            'business_id' => $business->id,
            'end_date' => now()->addMonth()->toDateString(),
            'created_by' => $user->id,
        ]);

        // Publish
        $published = $this->service->publish($promo, $user);
        $this->assertSame(PromotionStatus::Active, $published->status);
        $this->assertNotNull($published->published_at);
        $this->assertSame($user->id, $published->published_by);

        // Unpublish
        $unpublished = $this->service->unpublish($published, $user);
        $this->assertSame(PromotionStatus::Inactive, $unpublished->status);
    }

    public function test_it_fails_to_publish_expired_promotion(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        $promo = Promotion::factory()->create([
            'business_id' => $business->id,
            'end_date' => now()->subDay()->toDateString(),
            'created_by' => $user->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tanggal berakhir promo sudah terlewat');

        $this->service->publish($promo, $user);
    }
}
