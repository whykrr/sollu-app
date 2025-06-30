<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('merchant_id', 36);
            $table->string('slug', 200);
            $table->string('name', 200);
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 15)->default('active')->comment('options: active, grace, expired, inactive');
            $table->date('expired_at')->nullable();
            $table->boolean('is_main_outlet')->default(false);
            $table->timestamps();
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
