<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outlet_id');
            $table->uuid('shift_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->string('channel')->default('pos'); // pos, invoice
            $table->string('receipt_number')->index();
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 15, 4)->nullable();
            $table->string('promo_name')->nullable();
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('service_charge_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->string('payment_status')->default('unpaid'); // unpaid, partial, paid
            $table->string('status')->default('completed'); // hold, completed, void
            $table->boolean('is_offline')->default(false);
            $table->uuid('offline_id')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
