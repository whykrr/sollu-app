<?php

namespace Tests\Unit\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\User;
use App\Notifications\EmailVerifiedNotification;
use App\Notifications\LowStockAlertNotification;
use App\Notifications\NewEmployee;
use App\Notifications\NewOrderNotification;
use App\Notifications\WelcomeUser;
use Tests\TestCase;

class BaseNotificationTest extends TestCase
{
    public function test_welcome_user_payload_matches_standard_format(): void
    {
        $user = new User(['name' => 'John Doe', 'email' => 'john@example.com']);
        $notification = new WelcomeUser;

        $data = $notification->toDatabase($user);

        $this->assertSame(NotificationCategoryEnum::SYSTEM->value, $data['category']);
        $this->assertSame(NotificationTypeEnum::SUCCESS->value, $data['type']);
        $this->assertSame(NotificationScopeEnum::USER->value, $data['scope']);
        $this->assertNotEmpty($data['title']);
        $this->assertNotEmpty($data['message']);
        $this->assertNotEmpty($data['action_url']);
    }

    public function test_email_verified_notification_payload(): void
    {
        $user = new User(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $notification = new EmailVerifiedNotification;

        $data = $notification->toDatabase($user);

        $this->assertSame(NotificationCategoryEnum::SYSTEM->value, $data['category']);
        $this->assertSame(NotificationTypeEnum::SUCCESS->value, $data['type']);
        $this->assertSame('Email Berhasil Diverifikasi', $data['title']);
    }

    public function test_new_employee_notification_payload(): void
    {
        $user = new User(['name' => 'Staff A', 'email' => 'staff@example.com']);
        $notification = new NewEmployee('Secret123!');

        $data = $notification->toDatabase($user);

        $this->assertSame(NotificationCategoryEnum::SYSTEM->value, $data['category']);
        $this->assertSame(NotificationTypeEnum::INFO->value, $data['type']);
        $this->assertSame(NotificationScopeEnum::USER->value, $data['scope']);
        $this->assertStringContainsString('Ubah Password', $data['action_text']);
    }

    public function test_new_order_notification_payload(): void
    {
        $user = new User(['name' => 'Cashier', 'email' => 'cashier@example.com']);
        $notification = new NewOrderNotification(
            orderNumber: 'ORD-999',
            sourceName: 'GoFood',
            totalAmount: 150000.0,
            outletId: 'outlet-uuid-123',
            businessId: 'business-uuid-123',
            detailUrl: 'https://example.com/orders/999'
        );

        $data = $notification->toDatabase($user);

        $this->assertSame(NotificationCategoryEnum::ORDER->value, $data['category']);
        $this->assertSame(NotificationTypeEnum::INFO->value, $data['type']);
        $this->assertSame(NotificationScopeEnum::OUTLET->value, $data['scope']);
        $this->assertSame('Pesanan Baru #ORD-999', $data['title']);
        $this->assertSame('outlet-uuid-123', $data['outlet_id']);
        $this->assertSame('business-uuid-123', $data['business_id']);
    }

    public function test_low_stock_alert_notification_payload(): void
    {
        $user = new User(['name' => 'Manager', 'email' => 'manager@example.com']);
        $notification = new LowStockAlertNotification(
            itemName: 'Susu UHT',
            currentStock: 2,
            minimumStock: 10,
            unitName: 'Kotak',
            outletName: 'Outlet Sudirman',
            outletId: 'outlet-uuid-456',
            businessId: 'business-uuid-456'
        );

        $data = $notification->toDatabase($user);

        $this->assertSame(NotificationCategoryEnum::INVENTORY->value, $data['category']);
        $this->assertSame(NotificationTypeEnum::WARNING->value, $data['type']);
        $this->assertSame(NotificationScopeEnum::OUTLET->value, $data['scope']);
        $this->assertSame('Peringatan Stok Menipis', $data['title']);
    }
}
