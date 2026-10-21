<?php

declare(strict_types=1);

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
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('trial_end_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->timestamp('trial_end_at')->nullable()->after('logo');
        });

        // Reverse ETL: restore trial_end_at from active or latest trial subscription
        $trialSubscriptions = DB::table('subscriptions')
            ->where('is_trial', true)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($trialSubscriptions as $sub) {
            DB::table('businesses')
                ->where('id', $sub->business_id)
                ->whereNull('trial_end_at')
                ->update([
                    'trial_end_at' => $sub->expired_at,
                ]);
        }
    }
};
