<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop legacy tables if they still exist in the database schema
        Schema::dropIfExists('transaction_promos');
        Schema::dropIfExists('transaction_item_modifiers');
        Schema::dropIfExists('transaction_payments');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transaction_invoices');
        Schema::dropIfExists('transactions');

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            $table->string('channel', 50)->default('wholesale');
            $table->string('transaction_number', 50)->unique();
            $table->timestamp('transaction_date')->useCurrent();

            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->string('promo_name', 255)->nullable();
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('shipping_fee', 15, 4)->default(0);
            $table->decimal('service_charge_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->decimal('total_paid', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4)->default(0);

            $table->string('payment_status', 20)->default('draft');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('transaction_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('payment_term', 20)->default('cash');
            $table->string('payment_term_code', 20)->nullable()->default('custom');
            $table->string('status', 20)->default('draft');
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUuid('product_item_id')->nullable()->constrained('product_items')->restrictOnDelete();
            $table->foreignUuid('inventory_item_id')->nullable()->constrained('inventory_items')->restrictOnDelete();

            $table->string('product_name', 255);
            $table->string('sku', 100)->nullable();
            $table->string('uom_name', 50)->nullable();

            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('qty', 15, 4)->default(1);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->string('promo_name', 255)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignUuid('payment_method_id')->constrained('payment_methods')->restrictOnDelete();

            $table->decimal('amount', 15, 4);
            $table->decimal('change_amount', 15, 4)->default(0);
            $table->string('payment_reference', 255)->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->text('notes')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('transaction_promos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->uuid('promo_id')->nullable();
            $table->string('promo_name', 255);
            $table->string('discount_type', 20)->default('fixed');
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_promos');
        Schema::dropIfExists('transaction_payments');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transaction_invoices');
        Schema::dropIfExists('transactions');
    }
};
