<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;

class ExcelImportCompleted extends BaseNotification
{
    public function __construct(
        string $moduleName,
        int $successCount,
        int $failedCount,
        ?string $failedDownloadUrl = null,
        $expiresAt = null,
        ?string $actionText = null
    ) {
        $this->category = NotificationCategoryEnum::SYSTEM;
        $this->type = $failedCount > 0 ? NotificationTypeEnum::WARNING : NotificationTypeEnum::SUCCESS;
        $this->scope = NotificationScopeEnum::USER;
        $this->title = 'Impor '.$moduleName.' Selesai';

        $message = "Berhasil memproses {$successCount} data.";
        if ($failedCount > 0) {
            $message .= " Terdapat {$failedCount} baris data gagal.";
        }
        $this->message = $message;

        $this->actionUrl = $failedDownloadUrl;
        $this->actionText = $actionText ?? ($failedDownloadUrl ? ($failedCount > 0 ? 'Unduh Data Gagal' : 'Unduh Hasil Impor') : null);
        $this->expiresAt = $expiresAt;
        $this->meta = [
            'module' => $moduleName,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ];
    }
}
