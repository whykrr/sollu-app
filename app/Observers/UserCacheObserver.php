<?php

namespace App\Observers;

use App\Helpers\SummaryUser;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Auth\UserPermissionCacheService;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class UserCacheObserver
{
    protected function clearUserCache(Model $model, bool $isDelete = false): void
    {
        $permissionCacheService = app(UserPermissionCacheService::class);

        if ($model instanceof User) {
            SummaryUser::cacheDelete($model->id);

            // Only clear permission cache if auth-structural fields changed or record was deleted
            if ($isDelete || $model->wasChanged(['business_id', 'is_root_user', 'deleted_at'])) {
                $permissionCacheService->clearUserPermissions($model->id, $model->business_id);
            }

            return;
        }

        if ($model instanceof Role) {
            if ($model->business_id) {
                $permissionCacheService->clearBusinessPermissions($model->business_id);
            } else {
                $permissionCacheService->clearRuntimeCache();
            }

            if (method_exists($model, 'users')) {
                foreach ($model->users as $user) {
                    SummaryUser::cacheDelete($user->id);
                    $permissionCacheService->clearUserPermissions($user->id, $user->business_id);
                }
            }

            return;
        }

        if ($model instanceof Permission) {
            $permissionCacheService->clearRuntimeCache();

            return;
        }

        if ($model instanceof SubscriptionPlan) {
            SubscriptionPlan::clearCache();
            if ($model->relationLoaded('subscriptions') || $model->subscriptions()->exists()) {
                $businessIds = $model->subscriptions()->pluck('business_id')->unique();
                foreach ($businessIds as $businessId) {
                    Business::clearCache($businessId);
                }
                $userIds = User::whereIn('business_id', $businessIds)->pluck('id');
                foreach ($userIds as $userId) {
                    SummaryUser::cacheDelete($userId);
                    $permissionCacheService->clearUserPermissions($userId);
                }
            }

            return;
        }

        if ($model instanceof Subscription) {
            if ($model->business_id) {
                Business::clearCache($model->business_id);
                $userIds = User::where('business_id', $model->business_id)->pluck('id');
                foreach ($userIds as $userId) {
                    SummaryUser::cacheDelete($userId);
                }
            }

            return;
        }

        if ($model instanceof Feature) {
            Feature::clearCache();
            SubscriptionPlan::clearCache();

            return;
        }

        if ($model instanceof BusinessType) {
            BusinessType::clearCache();

            return;
        }

        if ($model instanceof Outlet) {
            if ($model->business_id) {
                $userIds = User::where('business_id', $model->business_id)->pluck('id');
                foreach ($userIds as $userId) {
                    SummaryUser::cacheDelete($userId);
                }
            }

            return;
        }

        if ($model instanceof Business) {
            Business::clearCache($model->id);
            if ($isDelete || $model->wasRecentlyCreated || $model->wasChanged(['status', 'deleted_at'])) {
                $permissionCacheService->clearBusinessPermissions($model->id);
            }

            $userIds = User::where('business_id', $model->id)->pluck('id');
            foreach ($userIds as $userId) {
                SummaryUser::cacheDelete($userId);
            }

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
        $this->clearUserCache($model, false);
    }

    public function deleted(Model $model): void
    {
        $this->clearUserCache($model, true);
    }
}
