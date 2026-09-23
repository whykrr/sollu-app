<?php

namespace Tests\Traits;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

trait InteractsWithBaseData
{
    protected static ?string $cachedTestPassword = null;

    /**
     * Dapatkan password ter-hash yang di-cache di memori untuk menghemat kalkulasi bcrypt.
     */
    protected function getTestHashedPassword(): string
    {
        return static::$cachedTestPassword ??= Hash::make('password');
    }

    /**
     * Inisialisasi dasar environment pengujian (Business, User, Outlet, Inventory Item).
     *
     * @return array{0: User, 1: Business, 2: Outlet, 3: InventoryItem}
     */
    protected function setupBaseData(bool $seedDatabase = true): array
    {
        if ($seedDatabase) {
            $this->seed(DatabaseSeeder::class);
        }

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Test User',
            'email' => 'user_'.uniqid().'@test.com',
            'password' => $this->getTestHashedPassword(),
        ]);

        setPermissionsTeamId($business->id);
        Permission::findOrCreate('business.*', 'web');
        $user->givePermissionTo('business.*');

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        $inventoryItem = InventoryItem::firstOrCreate([
            'business_id' => $business->id,
        ], [
            'name' => 'Flour',
            'sku' => 'FL-001',
            'item_type' => 'raw_material',
        ]);

        return [$user, $business, $outlet, $inventoryItem];
    }
}
