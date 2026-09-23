<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class WelcomeUser extends BaseNotification
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
        $this->title = 'Selamat Datang di '.config('app.name').'!';
        $this->message = 'Kami senang bisa mendampingi Anda dalam mengelola bisnis dengan lebih mudah, cepat, dan efisien. Akun Anda saat ini berada dalam masa percobaan gratis 15 hari.';
        $this->actionUrl = route('overview');
        $this->actionText = 'Masuk ke Dashboard';
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Selamat Datang di '.config('app.name').'!')
            ->greeting('Halo '.$notifiable->name.' 👋')
            ->line('Terima kasih telah mendaftar di '.config('app.name').'.')
            ->line('Kami senang bisa mendampingi Anda dalam mengelola bisnis dengan lebih mudah, cepat, dan efisien.')
            ->line('Akun Anda saat ini berada dalam masa percobaan gratis 15 hari. Nikmati semua fitur tanpa batasan untuk merasakan manfaat maksimal dari '.config('app.name').'.')
            ->action('Masuk ke Dashboard', route('overview'))
            ->line('Jika ada pertanyaan atau butuh bantuan, jangan ragu untuk menghubungi tim kami kapan saja.')
            ->salutation(new HtmlString('<strong>Salam hangat,<br>'.config('app.name').'</strong>'));
    }
}
