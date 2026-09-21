<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;

class ExcelExportCompleted extends BaseNotification
{
    public function __construct(string $moduleName, string $fileName, string $downloadUrl, $expiresAt = null)
    {
        $this->category = NotificationCategoryEnum::SYSTEM;
        $this->type = NotificationTypeEnum::SUCCESS;
        $this->scope = NotificationScopeEnum::USER;
        $this->title = 'Ekspor '.$moduleName.' Selesai';
        $this->message = 'File ekspor Excel Anda sudah siap diunduh.';
        $this->actionUrl = $downloadUrl;
        $this->actionText = 'Unduh File';
        $this->expiresAt = $expiresAt;
        $this->meta = [
            'module' => $moduleName,
            'file_name' => $fileName,
        ];
    }
}
