<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;
use App\Mail\OutletActivatedMail;
use App\Models\Outlet;

class OutletActivatedNotification extends BaseNotification
{
    public function __construct(public Outlet $outlet)
    {
        $this->category = NotificationCategoryEnum::SYSTEM;
        $this->type = NotificationTypeEnum::SUCCESS;
        $this->scope = NotificationScopeEnum::BUSINESS;
        $this->businessId = $this->outlet->business_id;
        $this->outletId = $this->outlet->id;
        $this->title = 'Penambahan Outlet Berhasil';
        $this->message = 'Pembayaran telah dikonfirmasi dan Outlet "'.$this->outlet->name.'" telah berhasil diaktifkan.';
        $this->actionUrl = route('settings.outlets.index');
        $this->actionText = 'Kelola Outlet';
        $this->meta = [
            'outlet_id' => $this->outlet->id,
            'outlet_name' => $this->outlet->name,
        ];
    }

    public function toMail(object $notifiable)
    {
        return (new OutletActivatedMail($this->outlet))->to($notifiable->email);
    }
}
