<?php

namespace Tests\Feature\Inventory;

use App\Enums\PermissionEnum;
use App\Enums\RoleTemplateEnum;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPermissionSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_inventory_update_permission_exists_in_database(): void
    {
        $this->assertDatabaseHas('permissions', [
            'name' => PermissionEnum::INVENTORY_UPDATE->value,
            'guard_name' => 'business',
        ]);
    }

    public function test_deprecated_warehouse_permissions_do_not_exist(): void
    {
        $deprecated = [
            'inventory.warehouse.view',
            'inventory.warehouse.create',
            'inventory.warehouse.update',
            'inventory.warehouse.delete',
        ];

        foreach ($deprecated as $perm) {
            $this->assertDatabaseMissing('permissions', [
                'name' => $perm,
            ]);
        }
    }

    public function test_role_templates_contain_inventory_update_and_no_deprecated_warehouse_permissions(): void
    {
        $templatesWithInventoryUpdate = [
            RoleTemplateEnum::INVENTORY_STAFF,
            RoleTemplateEnum::STORE_MANAGER_RETAIL,
            RoleTemplateEnum::MANAGER_FNB,
        ];

        foreach ($templatesWithInventoryUpdate as $template) {
            $perms = $template->permissions();
            $this->assertContains(
                PermissionEnum::INVENTORY_UPDATE->value,
                $perms,
                "Template {$template->value} harus menyertakan INVENTORY_UPDATE"
            );
        }

        // Verify none of the templates contain deprecated warehouse permissions
        $deprecatedValues = [
            'inventory.warehouse.view',
            'inventory.warehouse.create',
            'inventory.warehouse.update',
            'inventory.warehouse.delete',
        ];

        foreach (RoleTemplateEnum::cases() as $template) {
            $permValues = array_map(fn ($p) => $p instanceof PermissionEnum ? $p->value : (string) $p, $template->permissions());
            foreach ($deprecatedValues as $dep) {
                $this->assertNotContains(
                    $dep,
                    $permValues,
                    "Template {$template->value} tidak boleh memiliki permission deprecated: {$dep}"
                );
            }
        }
    }

    public function test_migration_handles_permission_sync_with_business_guard(): void
    {
        $migration = require database_path('migrations/2026_10_31_231000_sync_inventory_permissions.php');

        // Test down
        $migration->down();
        $this->assertDatabaseMissing('permissions', [
            'name' => 'inventory.update',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'inventory.warehouse.view',
            'guard_name' => 'business',
        ]);

        // Test up
        $migration->up();
        $this->assertDatabaseHas('permissions', [
            'name' => 'inventory.update',
            'guard_name' => 'business',
        ]);
        $this->assertDatabaseMissing('permissions', [
            'name' => 'inventory.warehouse.view',
        ]);
    }
}
