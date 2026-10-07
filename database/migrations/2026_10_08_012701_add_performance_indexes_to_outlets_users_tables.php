<?php

declare(strict_types=1);

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
        Schema::table('outlets', function (Blueprint $table) {
            $table->index(['business_id', 'is_active'], 'outlets_business_active_idx');
            $table->index(['business_id', 'deleted_at'], 'outlets_business_deleted_idx');
        });

        Schema::table('outlet_user', function (Blueprint $table) {
            $table->index('user_id', 'outlet_user_user_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('business_id', 'users_business_idx');
            $table->index(['business_id', 'deleted_at'], 'users_business_deleted_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_business_deleted_idx');
            $table->dropIndex('users_business_idx');
        });

        Schema::table('outlet_user', function (Blueprint $table) {
            $table->dropIndex('outlet_user_user_idx');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->dropIndex('outlets_business_deleted_idx');
            $table->dropIndex('outlets_business_active_idx');
        });
    }
};
