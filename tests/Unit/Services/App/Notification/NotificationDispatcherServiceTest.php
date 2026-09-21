<?php

namespace Tests\Unit\Services\App\Notification;

use App\Enums\NotificationScopeEnum;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Notifications\WelcomeUser;
use App\Services\App\Notification\NotificationDispatcherService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class NotificationDispatcherServiceTest extends TestCase
{
    public function test_send_to_user(): void
    {
        $user = new User;
        $user->setAttribute('business_id', 'biz-123');

        $service = Mockery::mock(NotificationDispatcherService::class)->makePartial();
        $notification = new WelcomeUser;

        // User Mock for notify
        $userMock = Mockery::mock($user)->makePartial();
        $userMock->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function ($notif) {
                return $notif->scope === NotificationScopeEnum::USER
                    && $notif->businessId === 'biz-123';
            }));

        $service->sendToUser($userMock, $notification);
    }

    public function test_send_to_business(): void
    {
        Notification::fake();

        $business = new Business;
        $business->setAttribute('id', 'biz-456');

        $user1 = new User(['name' => 'Owner', 'email' => 'owner@example.com']);
        $user1->setAttribute('id', 'user-1');

        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('role')->with(['owner', 'manager'])->andReturnSelf();
        $relation->shouldReceive('get')->andReturn(new Collection([$user1]));

        $businessMock = Mockery::mock($business)->makePartial();
        $businessMock->shouldReceive('users')->andReturn($relation);
        $businessMock->shouldReceive('getAttribute')->with('id')->andReturn('biz-456');

        $service = new NotificationDispatcherService;
        $notification = new WelcomeUser;

        $service->sendToBusiness($businessMock, $notification, ['owner', 'manager']);

        Notification::assertSentTo($user1, WelcomeUser::class);
        $this->assertSame(NotificationScopeEnum::BUSINESS, $notification->scope);
        $this->assertSame('biz-456', $notification->businessId);
    }

    public function test_send_to_outlet(): void
    {
        Notification::fake();

        $outlet = new Outlet;
        $outlet->setAttribute('id', 'outlet-789');
        $outlet->setAttribute('business_id', 'biz-789');

        $staff = new User(['name' => 'Staff', 'email' => 'staff@example.com']);
        $staff->setAttribute('id', 'staff-1');

        $relation = Mockery::mock(BelongsToMany::class);
        $relation->shouldReceive('get')->andReturn(new Collection([$staff]));

        $outletMock = Mockery::mock($outlet)->makePartial();
        $outletMock->shouldReceive('users')->andReturn($relation);
        $outletMock->shouldReceive('getAttribute')->with('id')->andReturn('outlet-789');
        $outletMock->shouldReceive('getAttribute')->with('business_id')->andReturn('biz-789');

        $service = new NotificationDispatcherService;
        $notification = new WelcomeUser;

        $service->sendToOutlet($outletMock, $notification, null);

        Notification::assertSentTo($staff, WelcomeUser::class);
        $this->assertSame(NotificationScopeEnum::OUTLET, $notification->scope);
        $this->assertSame('biz-789', $notification->businessId);
        $this->assertSame('outlet-789', $notification->outletId);
    }
}
