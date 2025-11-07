<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEmployee extends Notification
{
    use Queueable;

    protected $password;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $password)
    {
        $this->password = $password;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Selamat Datang di Sollu App!')
            ->markdown('mail.employee.new', [
                'user'            => $notifiable,
                'defaultPassword' => $this->password,
            ])
            ->action('Login Sekarang', route('dashboard.login'));
    }
}
