<?php

namespace Tests\Unit\Services\App\Master;

use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\User;
use App\Services\App\Master\AuditLogService;
use App\Services\App\Master\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogService $auditLogServiceMock;

    protected CategoryService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditLogServiceMock = Mockery::mock(AuditLogService::class);
        $this->auditLogServiceMock->shouldReceive('log')->andReturnNull();

        $this->service = new CategoryService($this->auditLogServiceMock);

        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Test Business',
            'owner_name' => 'John Doe',
            'email' => 'owner@example.com',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::factory()->create([
            'business_id' => $business->id,
        ]);
        Auth::login($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_gets_category_tree()
    {
        $parent = ProductCategory::create([
            'business_id' => $this->user->business_id,
            'name' => 'Food',
            'sort_order' => 1,
        ]);

        $child = ProductCategory::create([
            'business_id' => $this->user->business_id,
            'name' => 'Snacks',
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ]);

        $tree = $this->service->getTree();

        $this->assertCount(1, $tree);
        $this->assertEquals('Food', $tree->first()->name);
        $this->assertCount(1, $tree->first()->children);
        $this->assertEquals('Snacks', $tree->first()->children->first()->name);
    }

    public function test_it_creates_category()
    {
        $data = [
            'name' => 'Beverages',
        ];

        $category = $this->service->create($data);

        $this->assertInstanceOf(ProductCategory::class, $category);
        $this->assertEquals('Beverages', $category->name);
        $this->assertEquals(1, $category->sort_order);
        $this->assertEquals($this->user->business_id, $category->business_id);
    }

    public function test_it_creates_category_with_auto_sort_order()
    {
        ProductCategory::create([
            'business_id' => $this->user->business_id,
            'name' => 'First',
            'sort_order' => 5,
        ]);

        $category = $this->service->create([
            'name' => 'Second',
        ]);

        $this->assertEquals(6, $category->sort_order);
    }

    public function test_it_updates_category()
    {
        $category = ProductCategory::create([
            'business_id' => $this->user->business_id,
            'name' => 'Old Name',
            'sort_order' => 1,
        ]);

        $updated = $this->service->update($category, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'New Name',
        ]);
    }

    public function test_it_deletes_category_without_products()
    {
        $parent = ProductCategory::create([
            'business_id' => $this->user->business_id,
            'name' => 'Parent',
            'sort_order' => 1,
        ]);
        $child = ProductCategory::create([
            'business_id' => $this->user->business_id,
            'name' => 'Child',
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ]);

        $this->service->delete($parent);

        $this->assertSoftDeleted('product_categories', ['id' => $parent->id]);
        $this->assertSoftDeleted('product_categories', ['id' => $child->id]);
    }

    public function test_it_cannot_delete_category_with_products()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kategori tidak dapat dihapus karena masih digunakan oleh produk.');

        $category = ProductCategory::create([
            'business_id' => $this->user->business_id,
            'name' => 'Category',
            'sort_order' => 1,
        ]);

        Product::create([
            'business_id' => $this->user->business_id,
            'product_category_id' => $category->id,
            'name' => 'Product 1',
            'sku' => 'P-001',
            'price' => 1000,
            'product_type' => 'basic',
        ]);

        $this->service->delete($category);
    }

    public function test_it_cannot_delete_parent_category_if_child_has_products()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kategori tidak dapat dihapus karena masih digunakan oleh produk.');

        $parent = ProductCategory::create([
            'business_id' => $this->user->business_id,
            'name' => 'Parent',
            'sort_order' => 1,
        ]);
        $child = ProductCategory::create([
            'business_id' => $this->user->business_id,
            'name' => 'Child',
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ]);

        Product::create([
            'business_id' => $this->user->business_id,
            'product_category_id' => $child->id,
            'name' => 'Product 1',
            'sku' => 'P-001',
            'price' => 1000,
            'product_type' => 'basic',
        ]);

        $this->service->delete($parent);
    }

    public function test_it_reorders_categories()
    {
        $cat1 = ProductCategory::create(['business_id' => $this->user->business_id, 'name' => 'Cat1', 'sort_order' => 1]);
        $cat2 = ProductCategory::create(['business_id' => $this->user->business_id, 'name' => 'Cat2', 'sort_order' => 2]);

        $data = [
            ['id' => $cat1->id, 'parent_id' => null, 'sort_order' => 2],
            ['id' => $cat2->id, 'parent_id' => null, 'sort_order' => 1],
        ];

        $this->service->reorder($data);

        $this->assertEquals(2, $cat1->fresh()->sort_order);
        $this->assertEquals(1, $cat2->fresh()->sort_order);
    }
}
