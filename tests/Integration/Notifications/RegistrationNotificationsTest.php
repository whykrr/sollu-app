<?php

namespace Tests\Integration\Notifications;

use App\Models\User;
use App\Notifications\VerifyEmailBusiness;
use App\Notifications\WelcomeUser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_business_implements_should_queue(): void
    {
        $notification = new VerifyEmailBusiness;

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertTrue($notification->afterCommit);
    }

    public function test_welcome_user_implements_should_queue(): void
    {
        $notification = new WelcomeUser;

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertTrue($notification->afterCommit);
    }

    public function test_notifications_are_queued_when_sent(): void
    {
        Notification::fake();

        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->sendEmailVerificationNotification();
        $user->notify(new WelcomeUser($user));

        Notification::assertSentTo($user, VerifyEmailBusiness::class);
        Notification::assertSentTo($user, WelcomeUser::class);
    }
}
