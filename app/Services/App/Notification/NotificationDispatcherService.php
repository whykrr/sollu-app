<?php

namespace App\Services\App\Notification;

use App\Enums\NotificationScopeEnum;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Support\Facades\Notification;

class NotificationDispatcherService
{
    /**
     * Send a notification directly to a specific user.
     */
    public function sendToUser(User $user, BaseNotification $notification): void
    {
        $notification->scope = NotificationScopeEnum::USER;
        $notification->businessId = $user->business_id;

        $user->notify($notification);
    }

    /**
     * Send a notification to all authorized users in a business (e.g. Owner, Manager).
     *
     * @param  array<string>|null  $roles
     */
    public function sendToBusiness(Business $business, BaseNotification $notification, ?array $roles = ['owner', 'manager']): void
    {
        $notification->scope = NotificationScopeEnum::BUSINESS;
        $notification->businessId = $business->id;

        $query = $business->users();

        if (! empty($roles)) {
            setPermissionsTeamId($business->id);
            $query->role($roles);
        }

        $users = $query->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }

    /**
     * Send a notification to all staff assigned to a specific outlet.
     *
     * @param  array<string>|null  $roles
     */
    public function sendToOutlet(Outlet $outlet, BaseNotification $notification, ?array $roles = null): void
    {
        $notification->scope = NotificationScopeEnum::OUTLET;
        $notification->businessId = $outlet->business_id;
        $notification->outletId = $outlet->id;

        $query = $outlet->users();

        if (! empty($roles)) {
            setPermissionsTeamId($outlet->business_id);
            $query->role($roles);
        }

        $users = $query->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }
}
