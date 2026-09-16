<?php

use App\Enums\SubscriptionStatus;
use App\Models\Business;
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
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plans', 'is_custom')) {
                $table->dropColumn('is_custom');
            }
            if (Schema::hasColumn('subscription_plans', 'max_outlet')) {
                $table->dropColumn('max_outlet');
            }
        });

        // Cleanup duplicate active subscriptions for existing businesses:
        // Keep the latest active subscription and cancel older active ones.
        $businesses = Business::has('subscriptions')->with(['subscriptions' => function ($q) {
            $q->where('status', SubscriptionStatus::Active->value)->orderBy('created_at', 'desc');
        }])->get();

        foreach ($businesses as $business) {
            if ($business->subscriptions->count() > 1) {
                $latestActive = $business->subscriptions->first();
                $business->subscriptions()
                    ->where('id', '!=', $latestActive->id)
                    ->where('status', SubscriptionStatus::Active->value)
                    ->update([
                        'status' => SubscriptionStatus::Canceled->value,
                        'canceled_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('is_public');
            $table->integer('max_outlet')->nullable()->after('price_per_outlet');
        });
    }
};
