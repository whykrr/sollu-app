<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ResetPassword extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Atur Ulang Kata Sandi')
            ->greeting('Halo, '.$notifiable->name)
            ->line('Kami menerima permintaan untuk mereset kata sandi Anda.')
            ->action('Atur Ulang Kata Sandi', $url)
            ->line('Abaikan jika Anda tidak meminta permintaan ini.')
            ->salutation(new HtmlString('<strong>Salam hangat,<br>'.config('app.name').'</strong>'));
    }
}
