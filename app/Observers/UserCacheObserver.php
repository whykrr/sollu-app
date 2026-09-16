<?php

namespace App\Observers;

use App\Helpers\SummaryUser;
use Illuminate\Database\Eloquent\Model;

class UserCacheObserver
{
    protected function clearUserCache(Model $model): void
    {
        if ($model instanceof \App\Models\User) {
            SummaryUser::cacheDelete($model->id);

            return;
        }

        if ($model instanceof \App\Models\SubscriptionPlan) {
            \App\Models\SubscriptionPlan::clearCache();
            if ($model->relationLoaded('subscriptions') || $model->subscriptions()->exists()) {
                $businessIds = $model->subscriptions()->pluck('business_id')->unique();
                $userIds = \App\Models\User::whereIn('business_id', $businessIds)->pluck('id');
                foreach ($userIds as $userId) {
                    SummaryUser::cacheDelete($userId);
                }
            }

            return;
        }

        if ($model instanceof \App\Models\Feature) {
            \App\Models\Feature::clearCache();
            \App\Models\SubscriptionPlan::clearCache();

            return;
        }

        if (method_exists($model, 'users')) {
            foreach ($model->users as $user) {
                SummaryUser::cacheDelete($user->id);
            }
        } elseif (method_exists($model, 'business') && $model->business) {
            foreach ($model->business->users as $user) {
                SummaryUser::cacheDelete($user->id);
            }
        }
    }

    public function saved(Model $model): void
    {
        $this->clearUserCache($model);
    }

    public function deleted(Model $model): void
    {
        $this->clearUserCache($model);
    }
}
