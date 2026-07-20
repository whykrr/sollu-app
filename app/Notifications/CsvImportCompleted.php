<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CsvImportCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public $moduleName;
    public $successCount;
    public $failedCount;
    public $failedDownloadUrl;
    public $expiresAt;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $moduleName, int $successCount, int $failedCount, ?string $failedDownloadUrl = null, $expiresAt = null)
    {
        $this->moduleName = $moduleName;
        $this->successCount = $successCount;
        $this->failedCount = $failedCount;
        $this->failedDownloadUrl = $failedDownloadUrl;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = 'Impor ' . $this->moduleName . ' Selesai';
        $message = "Berhasil: {$this->successCount} baris.";
        
        if ($this->failedCount > 0) {
            $message .= " Gagal: {$this->failedCount} baris.";
        }

        return [
            'type' => $this->failedCount > 0 ? 'warning' : 'success',
            'title' => $title,
            'message' => $message,
            'action_url' => $this->failedDownloadUrl,
            'action_text' => $this->failedDownloadUrl ? 'Unduh Data Gagal' : null,
            'expires_at' => $this->expiresAt,
        ];
    }
}
