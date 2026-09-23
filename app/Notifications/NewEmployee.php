<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class NewEmployee extends BaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $password)
    {
        $this->afterCommit = true;
        $this->category = NotificationCategoryEnum::SYSTEM;
        $this->type = NotificationTypeEnum::INFO;
        $this->scope = NotificationScopeEnum::USER;
        $this->title = 'Selamat Bergabung di '.config('app.name').'!';
        $this->message = 'Akun karyawanmu telah dibuat. Demi keamanan, silakan segera ubah kata sandi default dan atur PIN kasirmu di menu profil.';
        $this->actionUrl = route('settings.account.profile');
        $this->actionText = 'Ubah Password & PIN';
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Selamat Datang di '.config('app.name').'!')
            ->markdown('mail.employee.new', [
                'user' => $notifiable,
                'defaultPassword' => $this->password,
                'actionUrl' => route('login'),
                'actionText' => 'Login Sekarang',
            ])
            ->action('Login Sekarang', route('login'));
    }
}
