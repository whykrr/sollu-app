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
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_register_page_displays_business_types_ordered_by_sort_order_and_filters_invisible(): void
    {
        BusinessType::clearCache();

        $typeThird = BusinessType::factory()->create([
            'name' => 'Toko Retail',
            'code' => 'retail',
            'sort_order' => 3,
            'is_visible' => true,
        ]);
        $typeFirst = BusinessType::factory()->create([
            'name' => 'F&B Restaurant',
            'code' => 'fnb',
            'sort_order' => 1,
            'is_visible' => true,
        ]);
        $typeSecond = BusinessType::factory()->create([
            'name' => 'Barbershop',
            'code' => 'barber',
            'sort_order' => 2,
            'is_visible' => true,
        ]);
        $typeHidden = BusinessType::factory()->create([
            'name' => 'Hidden Enterprise',
            'code' => 'enterprise',
            'sort_order' => 0,
            'is_visible' => false,
        ]);

        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('User/Register', false)
            ->has('business_types', 3)
            ->where('business_types.0.value', $typeFirst->id)
            ->where('business_types.0.label', 'F&B Restaurant')
            ->where('business_types.0.code', 'fnb')
            ->where('business_types.1.value', $typeSecond->id)
            ->where('business_types.1.label', 'Barbershop')
            ->where('business_types.1.code', 'barber')
            ->where('business_types.2.value', $typeThird->id)
            ->where('business_types.2.label', 'Toko Retail')
            ->where('business_types.2.code', 'retail')
        );
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
