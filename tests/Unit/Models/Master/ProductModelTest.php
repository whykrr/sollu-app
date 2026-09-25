<?php

namespace Tests\Unit\Models\Master;

use App\Models\Master\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    public function test_it_has_correct_boolean_casts(): void
    {
        $product = new Product;
        $casts = $product->getCasts();

        $this->assertEquals('boolean', $casts['has_variant'] ?? null);
        $this->assertEquals('boolean', $casts['has_modifier'] ?? null);
        $this->assertEquals('boolean', $casts['has_recipe'] ?? null);
        $this->assertEquals('boolean', $casts['track_inventory'] ?? null);
        $this->assertEquals('boolean', $casts['is_show'] ?? null);
        $this->assertEquals('boolean', $casts['sellable'] ?? null);
        $this->assertEquals('boolean', $casts['purchasable'] ?? null);
    }

    public function test_it_has_expected_fillable_and_sortable_attributes(): void
    {
        $product = new Product;

        $fillable = $product->getFillable();
        $this->assertContains('name', $fillable);
        $this->assertContains('business_id', $fillable);
        $this->assertContains('product_type', $fillable);
        $this->assertContains('product_category_id', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('image_url', $fillable);
        $this->assertContains('track_inventory', $fillable);

        $reflection = new ReflectionClass($product);
        $sortableProperty = $reflection->getProperty('sortable');
        $sortableProperty->setAccessible(true);
        $sortable = $sortableProperty->getValue($product);

        $this->assertEquals([
            'name',
            'code',
            'product_type',
            'created_at',
            'updated_at',
        ], $sortable);
    }

    public function test_cover_image_url_accessor_returns_null_when_empty(): void
    {
        $product = new Product(['image_url' => null]);

        $this->assertNull($product->cover_image_url);
    }

    public function test_cover_image_url_accessor_returns_storage_url_when_image_present(): void
    {
        Storage::fake('public');
        $product = new Product(['image_url' => 'products/sample.webp']);

        $this->assertNotNull($product->cover_image_url);
        $this->assertEquals(Storage::url('products/sample.webp'), $product->cover_image_url);
    }

    public function test_relationships_return_expected_relation_instances(): void
    {
        $product = new Product;

        $this->assertInstanceOf(BelongsTo::class, $product->category());
        $this->assertInstanceOf(HasMany::class, $product->variantGroups());
        $this->assertInstanceOf(BelongsToMany::class, $product->modifierGroups());
        $this->assertInstanceOf(HasMany::class, $product->recipeVersions());
        $this->assertInstanceOf(HasOne::class, $product->activeRecipe());
        $this->assertInstanceOf(HasMany::class, $product->bundleItems());
        $this->assertInstanceOf(HasMany::class, $product->componentOf());
        $this->assertInstanceOf(HasMany::class, $product->prices());
        $this->assertInstanceOf(BelongsToMany::class, $product->outlets());
        $this->assertInstanceOf(HasMany::class, $product->productItems());
        $this->assertInstanceOf(HasMany::class, $product->images());
    }
}
