<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // 1. Buat Schema audit terpisah
            DB::statement('CREATE SCHEMA IF NOT EXISTS audit;');

            // 2. Buat tabel master terpartisi di dalam schema audit
            DB::statement('
                CREATE TABLE IF NOT EXISTS audit.activity_logs (
                    id UUID NOT NULL,
                    business_id UUID NOT NULL,
                    outlet_id UUID NULL,
                    causer_type VARCHAR(255) NULL,
                    causer_id UUID NULL,
                    subject_type VARCHAR(255) NULL,
                    subject_id UUID NULL,
                    module VARCHAR(50) NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    description VARCHAR(500) NOT NULL,
                    properties JSONB NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
                    updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
                    PRIMARY KEY (id, created_at)
                ) PARTITION BY RANGE (created_at);
            ');

            // 3. Buat partisi awal (12 bulan ke belakang sampai 12 bulan ke depan)
            $start = Carbon::now()->subMonths(12)->startOfMonth();
            $end = Carbon::now()->addMonths(12)->startOfMonth();

            $current = $start->copy();
            while ($current->lessThanOrEqualTo($end)) {
                $partitionName = 'activity_logs_'.$current->format('Y_m');
                $from = $current->copy()->startOfMonth()->toDateTimeString();
                $to = $current->copy()->addMonth()->startOfMonth()->toDateTimeString();

                DB::statement("
                    CREATE TABLE IF NOT EXISTS audit.{$partitionName}
                    PARTITION OF audit.activity_logs
                    FOR VALUES FROM ('{$from}') TO ('{$to}');
                ");

                $current->addMonth();
            }

            // 4. Compound indexes pada tabel master
            DB::statement('CREATE INDEX IF NOT EXISTS idx_audit_logs_biz_created ON audit.activity_logs (business_id, created_at DESC);');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_audit_logs_biz_mod_created ON audit.activity_logs (business_id, module, created_at DESC);');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_audit_logs_biz_outlet ON audit.activity_logs (business_id, outlet_id, created_at DESC);');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_audit_logs_biz_causer ON audit.activity_logs (business_id, causer_id, created_at DESC);');
        } else {
            // Fallback untuk SQLite (Testing Environment)
            if (Schema::hasTable('activity_logs')) {
                Schema::dropIfExists('activity_logs');
            }

            Schema::create('activity_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('business_id')->index();
                $table->uuid('outlet_id')->nullable()->index();
                $table->nullableUuidMorphs('causer');
                $table->nullableUuidMorphs('subject');
                $table->string('module', 50)->index();
                $table->string('action', 100)->index();
                $table->string('description', 500);
                $table->json('properties')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'created_at']);
                $table->index(['business_id', 'module', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP SCHEMA IF EXISTS audit CASCADE;');
        } else {
            Schema::dropIfExists('activity_logs');
        }
    }
};
