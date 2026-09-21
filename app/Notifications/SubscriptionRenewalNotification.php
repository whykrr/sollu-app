<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;
use App\Mail\SubscriptionRenewalMail;
use App\Models\Business;
use App\Models\SubscriptionPlan;

class SubscriptionRenewalNotification extends BaseNotification
{
    public function __construct(
        public Business $business,
        public SubscriptionPlan $plan,
        public string $expiredAt,
        public int $daysRemaining
    ) {
        $this->category = NotificationCategoryEnum::SYSTEM;
        $this->type = NotificationTypeEnum::WARNING;
        $this->scope = NotificationScopeEnum::BUSINESS;
        $this->businessId = $this->business->id;
        $this->title = 'Pengingat Perpanjangan Langganan';
        $this->message = 'Langganan paket '.$this->plan->name.' tokomu akan berakhir dalam '.$this->daysRemaining.' hari ('.$this->expiredAt.'). Perpanjang sekarang agar layanan tidak terhenti.';
        $this->actionUrl = route('settings.billing.checkout', ['plan_id' => $this->plan->id]).'?is_renewal=1';
        $this->actionText = 'Perpanjang Sekarang';
        $this->meta = [
            'plan_id' => $this->plan->id,
            'plan_name' => $this->plan->name,
            'days_remaining' => $this->daysRemaining,
            'expired_at' => $this->expiredAt,
        ];
    }

    public function toMail(object $notifiable)
    {
        return (new SubscriptionRenewalMail($this->business, $this->plan, $this->expiredAt, $this->daysRemaining))
            ->to($notifiable->email);
    }
}
