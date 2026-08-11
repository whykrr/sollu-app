<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_item_modifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_item_id');
            $table->uuid('modifier_option_id')->nullable();
            $table->string('modifier_name');
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('qty', 15, 4)->default(0);
            $table->timestamps();

            $table->foreign('transaction_item_id')->references('id')->on('transaction_items')->cascadeOnDelete();
            $table->foreign('modifier_option_id')->references('id')->on('modifier_options')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_item_modifiers');
    }
};
