<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;

class EmailVerifiedNotification extends BaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->afterCommit = true;
        $this->category = NotificationCategoryEnum::SYSTEM;
        $this->type = NotificationTypeEnum::SUCCESS;
        $this->scope = NotificationScopeEnum::USER;
        $this->title = 'Email Berhasil Diverifikasi';
        $this->message = 'Alamat email tokomu telah berhasil diverifikasi. Seluruh fitur '.config('app.name').' kini siap digunakan secara maksimal.';
        $this->actionUrl = route('overview');
        $this->actionText = 'Lihat Dashboard';
    }
}
