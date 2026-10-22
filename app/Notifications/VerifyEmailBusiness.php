<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as NotificationsVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class VerifyEmailBusiness extends NotificationsVerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->afterCommit = true;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifikasi Email Anda')
            ->greeting('Halo '.$notifiable->name.' 👋')
            ->line('Terima kasih telah mendaftar. Silakan klik tombol di bawah ini untuk verifikasi email Anda:')
            ->action('Verifikasi Email', $verificationUrl)
            ->line('Jika Anda tidak mendaftar, abaikan email ini.')
            ->salutation(new HtmlString('<strong>Salam hangat,<br>'.config('app.name').'</strong>'));
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
