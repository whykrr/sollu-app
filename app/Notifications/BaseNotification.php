<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public NotificationCategoryEnum $category = NotificationCategoryEnum::SYSTEM;

    public NotificationTypeEnum $type = NotificationTypeEnum::INFO;

    public NotificationScopeEnum $scope = NotificationScopeEnum::USER;

    public ?string $businessId = null;

    public ?string $outletId = null;

    public string $title = 'Pemberitahuan';

    public string $message = '';

    public ?string $actionUrl = null;

    public ?string $actionText = null;

    public ?string $expiresAt = null;

    public array $meta = [];

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($this, 'toMail')) {
            $channels[] = 'mail';
        }

        if (! in_array(config('broadcasting.default'), ['log', 'null'])) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => $this->category->value,
            'type' => $this->type->value,
            'scope' => $this->scope->value,
            'business_id' => $this->businessId,
            'outlet_id' => $this->outletId,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'expires_at' => $this->expiresAt,
            'meta' => $this->meta,
        ];
    }

    /**
     * Get the array representation of the notification for backward compatibility.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
