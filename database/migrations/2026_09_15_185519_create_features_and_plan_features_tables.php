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
        Schema::create('features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('module')->default('pos')->index();
            $table->string('group')->index();
            $table->string('group_label');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->after('is_active');
            $table->boolean('is_custom')->default(false)->after('is_public');
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->foreignUuid('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->foreignUuid('feature_id')->constrained('features')->cascadeOnDelete();
            $table->primary(['plan_id', 'feature_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'is_custom']);
        });

        Schema::dropIfExists('features');
    }
};
