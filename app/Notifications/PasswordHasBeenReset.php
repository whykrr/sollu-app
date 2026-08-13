<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class PasswordHasBeenReset extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kata Sandi Anda Berhasil Diperbarui')
            ->greeting('Halo '.$notifiable->name)
            ->line('Kami ingin memberitahu bahwa kata sandi akun Anda telah berhasil diperbarui.')
            ->line('Jika Anda merasa tidak melakukan perubahan ini, segera hubungi tim dukungan kami.')
            ->action('Masuk ke Aplikasi', route('login'))
            ->salutation(new HtmlString('<strong>Salam hangat,<br>'.config('app.name').'</strong>'));
    }
}
