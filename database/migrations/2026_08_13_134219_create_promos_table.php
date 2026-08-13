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
        Schema::create('promos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('promo_type'); // percentage / fixed
            $table->string('target_type'); // product / bill
            $table->decimal('discount_value', 15, 4);
            $table->decimal('max_discount', 15, 4)->nullable();
            $table->boolean('applies_to_all_outlets')->default(true);
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->default('draft'); // draft, active, inactive
            $table->uuid('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
