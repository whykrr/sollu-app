<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CsvExportCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public $moduleName;

    public $fileName;

    public $downloadUrl;

    public $expiresAt;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $moduleName, string $fileName, string $downloadUrl, $expiresAt = null)
    {
        $this->moduleName = $moduleName;
        $this->fileName = $fileName;
        $this->downloadUrl = $downloadUrl;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'success',
            'title' => 'Ekspor '.$this->moduleName.' Selesai',
            'message' => 'File ekspor Anda sudah siap diunduh.',
            'action_url' => $this->downloadUrl,
            'action_text' => 'Unduh File',
            'expires_at' => $this->expiresAt,
        ];
    }
}
