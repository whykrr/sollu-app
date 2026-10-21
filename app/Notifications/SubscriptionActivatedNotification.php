<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;
use App\Mail\SubscriptionActivatedMail;
use App\Models\Business;
use App\Models\SubscriptionPlan;

class SubscriptionActivatedNotification extends BaseNotification
{
    public function __construct(
        public Business $business,
        public SubscriptionPlan $plan,
        public string $expiredAt
    ) {
        $this->category = NotificationCategoryEnum::SYSTEM;
        $this->type = NotificationTypeEnum::SUCCESS;
        $this->scope = NotificationScopeEnum::USER;
        $this->businessId = $this->business->id;
        $this->title = 'Langganan Aktif';
        $this->message = 'Langganan paket '.$this->plan->name.' telah aktif sampai '.$this->expiredAt.'.';
        $this->actionUrl = route('settings.billing.plans');
        $this->actionText = 'Lihat Paket';

        $this->meta = [
            'plan_id' => $this->plan->id,
            'plan_name' => $this->plan->name,
            'expired_at' => $this->expiredAt,
        ];
    }

    public function toMail(object $notifiable)
    {
        return (new SubscriptionActivatedMail($this->business, $this->plan, $this->expiredAt))
            ->to($notifiable->email);
    }
}
