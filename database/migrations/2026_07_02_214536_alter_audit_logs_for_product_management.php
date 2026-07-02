<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->uuid('business_id')->nullable()->after('id');
            $table->string('entity_type')->nullable()->after('actor_id');
            $table->uuid('entity_id')->nullable()->after('entity_type');
            $table->jsonb('before_value')->nullable()->after('action');
            $table->jsonb('after_value')->nullable()->after('before_value');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'business_id',
                'entity_type',
                'entity_id',
                'before_value',
                'after_value',
            ]);
        });
    }
};
