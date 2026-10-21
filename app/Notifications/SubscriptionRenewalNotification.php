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
        public int $daysRemaining,
        public bool $isTrial = false
    ) {
        $this->category = NotificationCategoryEnum::SYSTEM;
        $this->type = $this->daysRemaining <= 0 ? NotificationTypeEnum::DANGER : NotificationTypeEnum::WARNING;
        $this->scope = NotificationScopeEnum::USER;
        $this->businessId = $this->business->id;

        if ($this->isTrial) {
            if ($this->daysRemaining > 0) {
                $this->title = 'Masa Uji Coba Segera Berakhir';
                $this->message = "Masa uji coba (trial) tokomu akan berakhir dalam {$this->daysRemaining} hari ({$this->expiredAt}). Pilih paket langganan sekarang agar operasional toko tetap lancar.";
            } elseif ($this->daysRemaining === 0) {
                $this->title = 'Masa Uji Coba Berakhir Hari Ini';
                $this->message = "Masa uji coba tokomu berakhir hari ini ({$this->expiredAt}). Segera aktifkan paket langganan tokomu.";
            } else {
                $this->title = 'Masa Uji Coba Telah Berakhir';
                $this->message = "Masa uji coba tokomu telah berakhir ({$this->expiredAt}). Berlangganan sekarang untuk membuka kembali akses fitur tokomu.";
            }
            $this->actionUrl = route('settings.billing.plans');
            $this->actionText = 'Pilih Paket';
        } else {
            if ($this->daysRemaining > 0) {
                $this->title = 'Pengingat Perpanjangan Langganan';
                $this->message = "Langganan paket {$this->plan->name} tokomu akan berakhir dalam {$this->daysRemaining} hari ({$this->expiredAt}). Perpanjang sekarang agar layanan tidak terhenti.";
            } elseif ($this->daysRemaining === 0) {
                $this->title = 'Langganan Berakhir Hari Ini';
                $this->message = "Langganan paket {$this->plan->name} tokomu berakhir hari ini ({$this->expiredAt}). Segera lakukan pembayaran perpanjangan.";
            } else {
                $this->title = 'Langganan Memasuki Masa Tenggang';
                $this->message = "Langganan paket {$this->plan->name} tokomu telah melewati jatuh tempo. Tokomu saat ini dalam masa tenggang. Segera perpanjang agar operasional tidak dibatasi.";
            }
            $this->actionUrl = route('settings.billing.checkout', ['plan_id' => $this->plan->id]).'?is_renewal=1';
            $this->actionText = 'Perpanjang Sekarang';
        }

        $this->meta = [
            'plan_id' => $this->plan->id,
            'plan_name' => $this->plan->name,
            'days_remaining' => $this->daysRemaining,
            'expired_at' => $this->expiredAt,
            'is_trial' => $this->isTrial,
        ];
    }

    public function toMail(object $notifiable)
    {
        return (new SubscriptionRenewalMail($this->business, $this->plan, $this->expiredAt, $this->daysRemaining))
            ->to($notifiable->email);
    }
}
