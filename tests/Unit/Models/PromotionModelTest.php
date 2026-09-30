<?php

namespace Tests\Unit\Models;

use App\Enums\ProductTypeEnum;
use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Promotion\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionModelTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_it_creates_promotion_with_correct_casts(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        $promotion = Promotion::factory()->create([
            'business_id' => $business->id,
            'name' => 'Diskon Opening 20%',
            'description' => 'Promo pembukaan toko baru',
            'application_mode' => PromotionApplicationMode::Manual,
            'promo_code' => 'OPENING20',
            'target_scope' => PromotionTargetScope::Transaction,
            'discount_type' => PromotionDiscountType::Percentage,
            'discount_value' => 20.0,
            'max_discount_amount' => 50000.0,
            'min_subtotal' => 100000.0,
            'min_quantity' => 2.0,
            'applies_to_all_outlets' => true,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'start_time' => '14:00',
            'end_time' => '17:00',
            'days_of_week' => [1, 2, 3],
            'status' => PromotionStatus::Draft,
            'created_by' => $user->id,
        ]);

        $this->assertSame('Diskon Opening 20%', $promotion->name);
        $this->assertSame(PromotionApplicationMode::Manual, $promotion->application_mode);
        $this->assertSame('OPENING20', $promotion->promo_code);
        $this->assertSame(PromotionTargetScope::Transaction, $promotion->target_scope);
        $this->assertSame(PromotionDiscountType::Percentage, $promotion->discount_type);
        $this->assertSame(20.0, $promotion->discount_value);
        $this->assertSame(50000.0, $promotion->max_discount_amount);
        $this->assertSame(100000.0, $promotion->min_subtotal);
        $this->assertSame(2.0, $promotion->min_quantity);
        $this->assertTrue($promotion->applies_to_all_outlets);
        $this->assertSame([1, 2, 3], $promotion->days_of_week);
        $this->assertSame(PromotionStatus::Draft, $promotion->status);
    }

    public function test_it_handles_pivot_relations(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);
        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Pusat',
            'code' => 'OUT-01',
        ]);

        $category = ProductCategory::create([
            'business_id' => $business->id,
            'name' => 'Minuman',
            'created_by' => $user->id,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'product_category_id' => $category->id,
            'product_type' => ProductTypeEnum::BASIC->value,
            'name' => 'Kopi Susu',
        ]);

        $productItem = ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'item_type' => 'variant_sku',
            'name' => 'Kopi Susu Large',
            'sku' => 'KOP-SUS-LG',
        ]);

        $promotion = Promotion::factory()->create([
            'business_id' => $business->id,
            'applies_to_all_outlets' => false,
            'target_scope' => PromotionTargetScope::Variant,
            'created_by' => $user->id,
        ]);

        $promotion->outlets()->attach($outlet->id);
        $promotion->categories()->attach($category->id);
        $promotion->products()->attach($product->id);
        $promotion->productItems()->attach($productItem->id);

        $this->assertCount(1, $promotion->outlets);
        $this->assertSame($outlet->id, $promotion->outlets->first()->id);

        $this->assertCount(1, $promotion->categories);
        $this->assertSame($category->id, $promotion->categories->first()->id);

        $this->assertCount(1, $promotion->products);
        $this->assertSame($product->id, $promotion->products->first()->id);

        $this->assertCount(1, $promotion->productItems);
        $this->assertSame($productItem->id, $promotion->productItems->first()->id);
    }

    public function test_scope_active_and_filters(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create(['business_id' => $business->id]);

        // Active promo
        $activePromo = Promotion::factory()->active()->create([
            'business_id' => $business->id,
            'name' => 'Promo Aktif Kopi',
            'promo_code' => 'KOPIAKTIF',
            'target_scope' => PromotionTargetScope::Product,
            'created_by' => $user->id,
        ]);

        // Expired promo
        $expiredPromo = Promotion::factory()->expired()->create([
            'business_id' => $business->id,
            'name' => 'Promo Lampau',
            'created_by' => $user->id,
        ]);

        // Draft promo
        $draftPromo = Promotion::factory()->create([
            'business_id' => $business->id,
            'name' => 'Promo Draf Baru',
            'created_by' => $user->id,
        ]);

        // Test scopeActive
        $activeList = Promotion::query()->active()->get();
        $this->assertTrue($activeList->contains('id', $activePromo->id));
        $this->assertFalse($activeList->contains('id', $expiredPromo->id));
        $this->assertFalse($activeList->contains('id', $draftPromo->id));

        // Test scopeFilters search by name
        $searchResult = Promotion::query()->filters(['search' => 'Kopi'])->get();
        $this->assertCount(1, $searchResult);
        $this->assertSame($activePromo->id, $searchResult->first()->id);

        // Test scopeFilters search by promo_code
        $codeResult = Promotion::query()->filters(['search' => 'KOPIAKTIF'])->get();
        $this->assertCount(1, $codeResult);
        $this->assertSame($activePromo->id, $codeResult->first()->id);

        // Test scopeFilters by status
        $statusResult = Promotion::query()->filters(['status' => PromotionStatus::Draft])->get();
        $this->assertCount(1, $statusResult);
        $this->assertSame($draftPromo->id, $statusResult->first()->id);

        // Test scopeFilters by target_scope
        $targetResult = Promotion::query()->filters(['target_scope' => PromotionTargetScope::Product])->get();
        $this->assertCount(1, $targetResult);
        $this->assertSame($activePromo->id, $targetResult->first()->id);
    }
}
