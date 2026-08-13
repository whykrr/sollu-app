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
        Schema::table('customers', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('address');
            $table->string('gender')->nullable()->after('birthdate');
            $table->text('notes')->nullable()->after('gender');
            $table->boolean('is_active')->default(true)->after('notes');
            $table->uuid('created_by')->nullable()->after('is_active');

            // Change phone to required and add composite unique key
            $table->string('phone')->nullable(false)->change();
            $table->dropIndex(['phone']);
            $table->unique(['business_id', 'phone']);

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropUnique(['business_id', 'phone']);
            $table->index(['phone']);
            $table->string('phone')->nullable()->change();

            $table->dropColumn([
                'birthdate',
                'gender',
                'notes',
                'is_active',
                'created_by',
            ]);
        });
    }
};
