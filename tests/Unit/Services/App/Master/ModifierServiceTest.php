<?php

namespace Tests\Unit\Services\App\Master;

use App\Models\Business;
use App\Models\Master\ModifierGroup;
use App\Services\App\Master\AuditLogService;
use App\Services\App\Master\ModifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ModifierServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogService $auditLogServiceMock;

    protected ModifierService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditLogServiceMock = Mockery::mock(AuditLogService::class);
        $this->auditLogServiceMock->shouldReceive('log')->andReturnNull();

        $this->service = new ModifierService($this->auditLogServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createTenant(): Business
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        return Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);
    }

    public function test_it_creates_modifier_group()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $business = $this->createTenant();

        $data = [
            'business_id' => $business->id,
            'name' => 'Toppings',
            'selection_type' => 'multi',
            'max_select' => 2,
            'is_required' => false,
            'options' => [
                ['name' => 'Cheese', 'additional_price' => 5000, 'is_default' => false],
                ['name' => 'Boba', 'additional_price' => 3000, 'is_default' => true],
            ],
        ];

        $group = $this->service->createGroup($data);

        $this->assertInstanceOf(ModifierGroup::class, $group);
        $this->assertEquals('Toppings', $group->name);
        $this->assertEquals('multi', $group->selection_type);
        $this->assertCount(2, $group->options);

        $this->assertDatabaseHas('modifier_groups', [
            'id' => $group->id,
            'name' => 'Toppings',
        ]);

        $this->assertDatabaseHas('modifier_options', [
            'modifier_group_id' => $group->id,
            'name' => 'Cheese',
            'additional_price' => 5000,
        ]);
    }

    public function test_it_updates_modifier_group()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $business = $this->createTenant();

        $group = ModifierGroup::create([
            'business_id' => $business->id,
            'name' => 'Old Name',
            'selection_type' => 'single',
        ]);
        $group->options()->create(['name' => 'Old Option']);

        $updateData = [
            'name' => 'New Name',
            'selection_type' => 'multi',
            'max_select' => 3,
            'is_required' => true,
            'options' => [
                ['name' => 'New Option 1', 'additional_price' => 1000],
                ['name' => 'New Option 2', 'additional_price' => 2000],
            ],
        ];

        $updatedGroup = $this->service->updateGroup($group, $updateData);

        $this->assertEquals('New Name', $updatedGroup->name);
        $this->assertEquals('multi', $updatedGroup->selection_type);
        $this->assertEquals(3, $updatedGroup->max_select);
        $this->assertTrue($updatedGroup->is_required);

        $this->assertCount(2, $updatedGroup->options); // new options replaced old
        $this->assertDatabaseMissing('modifier_options', [
            'modifier_group_id' => $group->id,
            'name' => 'Old Option',
        ]);
        $this->assertDatabaseHas('modifier_options', [
            'modifier_group_id' => $group->id,
            'name' => 'New Option 1',
        ]);
    }

    public function test_it_deletes_modifier_group()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $business = $this->createTenant();

        $group = ModifierGroup::create([
            'business_id' => $business->id,
            'name' => 'Delete Me',
            'selection_type' => 'single',
        ]);
        $option = $group->options()->create(['name' => 'Option']);

        $this->service->deleteGroup($group);

        $this->assertDatabaseMissing('modifier_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('modifier_options', ['id' => $option->id]);
    }
}
