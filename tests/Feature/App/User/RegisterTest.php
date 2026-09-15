<?php

namespace Tests\Feature\App\User;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Models\BusinessType;
use App\Notifications\VerifyEmailBusiness;
use App\Notifications\WelcomeUser;
use App\Services\App\Outlet\OutletProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_can_be_rendered(): void
    {
        BusinessType::factory()->forType('restaurant')->create();

        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_user_can_register_business_and_notifications_are_queued(): void
    {
        Notification::fake();
        \Illuminate\Support\Facades\Event::fake([\App\Events\User\BusinessRegistered::class]);

        $this->seed(\Database\Seeders\Production\RolePermissionSeeder::class);

        $type = BusinessType::factory()->forType('minimarket')->create();

        $provisioningMock = Mockery::mock(OutletProvisioningService::class);
        $provisioningMock->shouldReceive('provisionAll')->once()->andReturnNull();
        $this->app->instance(OutletProvisioningService::class, $provisioningMock);

        $payload = [
            'name' => 'Warung Makan Barokah',
            'owner_name' => 'Ahmad Dahlan',
            'outlet_name' => 'Pusat Solo',
            'email' => 'ahmad@barokah.test',
            'phone' => '081234567890',
            'business_type_id' => $type->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.store'), $payload);

        $response->assertRedirect(route('overview'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::REGISTER_SUCCESS);

        // Verify authenticated as business guard
        $this->assertTrue(Auth::guard('business')->check());
        $this->assertEquals('ahmad@barokah.test', Auth::guard('business')->user()->email);

        // Verify async notifications were dispatched
        Notification::assertSentTo(
            Auth::guard('business')->user(),
            VerifyEmailBusiness::class
        );
        Notification::assertSentTo(
            Auth::guard('business')->user(),
            WelcomeUser::class
        );

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\User\BusinessRegistered::class);
    }

    public function test_registration_validation_fails_with_invalid_data(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'business_type_id' => 999999,
        ]);

        $response->assertSessionHasErrors(['name', 'owner_name', 'outlet_name', 'email', 'phone', 'business_type_id', 'password']);
    }
}
