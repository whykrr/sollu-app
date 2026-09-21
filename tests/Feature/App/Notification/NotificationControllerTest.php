<?php

namespace Tests\Feature\App\Notification;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use App\Notifications\NewOrderNotification;
use App\Notifications\WelcomeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $businessType = BusinessType::factory()->create();

        $this->business = Business::create([
            'name' => 'Test Coffee',
            'owner_name' => 'Owner',
            'email' => 'coffee@example.com',
            'phone' => '08123456789',
            'status' => 'active',
            'business_type_id' => $businessType->id,
            'trial_end_at' => now()->addDays(15),
        ]);

        $this->outlet = $this->business->outlets()->create([
            'name' => 'Outlet Utama',
            'is_main_outlet' => true,
        ]);

        $this->user = $this->business->users()->create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'is_root_user' => true,
        ]);

        $this->user->outlets()->attach($this->outlet->id);
    }

    public function test_can_list_notifications_with_category_filtering(): void
    {
        $this->actingAs($this->user, 'business');

        // 1. System notification
        $this->user->notify(new WelcomeUser);

        // 2. Order notification
        $this->user->notify(new NewOrderNotification(
            orderNumber: 'ORD-001',
            sourceName: 'GoFood',
            totalAmount: 50000.0,
            outletId: $this->outlet->id,
            businessId: $this->business->id
        ));

        // 3. Inventory notification
        $this->user->notify(new LowStockAlertNotification(
            itemName: 'Kopi Arabika',
            currentStock: 1,
            minimumStock: 5,
            unitName: 'Kg',
            outletName: $this->outlet->name,
            outletId: $this->outlet->id,
            businessId: $this->business->id
        ));

        // Filter 'all' -> should return 3
        $responseAll = $this->getJson(route('api.internal.notifications.index', ['filter' => 'all']));
        $responseAll->assertOk()
            ->assertJsonPath('unread_count', 3)
            ->assertJsonCount(3, 'notifications.data');

        // Filter 'system' -> should return 1
        $responseSystem = $this->getJson(route('api.internal.notifications.index', ['filter' => 'system']));
        $responseSystem->assertOk()
            ->assertJsonCount(1, 'notifications.data')
            ->assertJsonPath('notifications.data.0.data.category', 'system');

        // Filter 'order' -> should return 1
        $responseOrder = $this->getJson(route('api.internal.notifications.index', ['filter' => 'order']));
        $responseOrder->assertOk()
            ->assertJsonCount(1, 'notifications.data')
            ->assertJsonPath('notifications.data.0.data.category', 'order');

        // Filter 'inventory' -> should return 1
        $responseInventory = $this->getJson(route('api.internal.notifications.index', ['filter' => 'inventory']));
        $responseInventory->assertOk()
            ->assertJsonCount(1, 'notifications.data')
            ->assertJsonPath('notifications.data.0.data.category', 'inventory');
    }

    public function test_can_mark_notification_as_read(): void
    {
        $this->actingAs($this->user, 'business');
        $this->user->notify(new WelcomeUser);

        $notification = $this->user->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $response = $this->patchJson(route('api.internal.notifications.markAsRead', $notification->id));
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame(0, $this->user->fresh()->unreadNotifications()->count());
    }

    public function test_can_mark_all_notifications_as_read(): void
    {
        $this->actingAs($this->user, 'business');
        $this->user->notify(new WelcomeUser);
        $this->user->notify(new WelcomeUser);

        $this->assertSame(2, $this->user->unreadNotifications()->count());

        $response = $this->postJson(route('api.internal.notifications.markAllAsRead'));
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame(0, $this->user->fresh()->unreadNotifications()->count());
    }

    public function test_can_delete_notification(): void
    {
        $this->actingAs($this->user, 'business');
        $this->user->notify(new WelcomeUser);

        $notification = $this->user->notifications()->first();

        $response = $this->deleteJson(route('api.internal.notifications.destroy', $notification->id));
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_cannot_access_other_user_notifications(): void
    {
        $otherUser = $this->business->users()->create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => 'password',
        ]);
        $otherUser->notify(new WelcomeUser);
        $otherNotification = $otherUser->notifications()->first();

        $this->actingAs($this->user, 'business');

        $response = $this->patchJson(route('api.internal.notifications.markAsRead', $otherNotification->id));
        $response->assertNotFound();
    }
}
