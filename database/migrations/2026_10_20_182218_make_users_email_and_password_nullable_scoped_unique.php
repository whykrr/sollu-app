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
        Schema::table('users', function (Blueprint $table) {
            // 1. Drop existing global unique indexes
            $table->dropUnique('users_email_unique');
            $table->dropUnique('users_phone_unique');

            // 2. Make email and password nullable for cashier / POS-only users
            $table->string('email', 200)->nullable()->change();
            $table->string('password', 255)->nullable()->change();

            // 3. Add composite unique scoped per business
            $table->unique(['business_id', 'email'], 'users_business_id_email_unique');
            $table->unique(['business_id', 'phone'], 'users_business_id_phone_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Drop composite unique scoped per business (LIFO order)
            $table->dropUnique('users_business_id_phone_unique');
            $table->dropUnique('users_business_id_email_unique');

            // 2. Revert password and email to not-null with full original attributes
            $table->string('password', 255)->nullable(false)->change();
            $table->string('email', 200)->nullable(false)->change();

            // 3. Restore global unique indexes
            $table->unique('phone', 'users_phone_unique');
            $table->unique('email', 'users_email_unique');
        });
    }
};
