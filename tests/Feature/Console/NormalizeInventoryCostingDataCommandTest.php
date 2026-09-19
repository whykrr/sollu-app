<?php

namespace Tests\Feature\Console;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizeInventoryCostingDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_inventory_costing_command_executes_successfully(): void
    {
        $type = BusinessType::create(['name' => 'Retail', 'code' => 'retail_norm']);
        $business = Business::create([
            'name' => 'Toko Normalisasi',
            'owner_name' => 'Owner',
            'email' => 'norm@test.com',
            'phone' => '081234567890',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(14),
        ]);

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Utama',
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 1',
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Produk A',
            'product_type' => 'basic',
            'code' => 'PRD-A',
        ]);

        $invItem = InventoryItem::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'item_type' => 'raw_material',
            'name' => 'Produk A Raw',
            'sku' => 'SKU-A',
        ]);

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $invItem->id,
            'current_stock' => 10,
            'average_cost' => 10000,
            'last_cost' => 10000,
            'total_value' => 100000,
        ]);

        $transaction = Transaction::create([
            'outlet_id' => $outlet->id,
            'transaction_number' => 'TRX-001',
            'subtotal' => 20000,
            'total' => 20000,
            'status' => 'completed',
        ]);

        $trxItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name' => 'Produk A',
            'price' => 20000,
            'qty' => 2,
            'subtotal' => 40000,
            'unit_cogs' => 0,
            'cogs_amount' => 0,
        ]);

        $this->artisan('inventory:normalize-costing', ['--business' => $business->id])
            ->assertSuccessful();

        $trxItem->refresh();
        $this->assertEquals(10000.0, (float) $trxItem->unit_cogs);
        $this->assertEquals(20000.0, (float) $trxItem->cogs_amount);
    }
}
