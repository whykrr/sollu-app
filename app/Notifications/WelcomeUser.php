<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WelcomeUser extends Notification
{
    use Queueable;

    /**
 * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Selamat Datang di Sollu App!')
            ->greeting('Halo ' . $notifiable->name . ' 👋')
            ->line('Terima kasih telah mendaftar di Sollu App.')
            ->line('Kami senang bisa mendampingi Anda dalam mengelola bisnis dengan lebih mudah, cepat, dan efisien.')
            ->line('Akun Anda saat ini berada dalam masa percobaan gratis. Nikmati semua fitur premium tanpa batasan untuk merasakan manfaat maksimal dari Sollu.')
            ->action('Masuk ke Dashboard', route('overview'))
            ->line('Jika ada pertanyaan atau butuh bantuan, jangan ragu untuk menghubungi tim kami kapan saja.')
            ->salutation(new HtmlString('<strong>Salam hangat,<br>' . config('app.name') . '</strong>'));
    }

    public function toArray($notifiable)
    {
        return [
            'title'   => 'Selamat Datang di Sollu App!',
            'message' => 'Kami senang bisa mendampingi Anda dalam mengelola bisnis dengan lebih mudah, cepat, dan efisien. Akun Anda saat ini berada dalam masa percobaan gratis. Nikmati semua fitur premium tanpa batasan untuk merasakan manfaat maksimal dari Sollu.',
        ];
    }
}
