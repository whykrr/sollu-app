<?php

namespace Tests\Feature\App\User;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Events\User\BusinessRegistered;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\User;
use App\Notifications\VerifyEmailBusiness;
use App\Notifications\WelcomeUser;
use App\Services\App\Outlet\OutletProvisioningService;
use Database\Seeders\Production\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
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
        Event::fake([BusinessRegistered::class]);

        $this->seed(RolePermissionSeeder::class);

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

        Event::assertDispatched(BusinessRegistered::class);
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

    public function test_former_employee_can_register_new_business_with_same_email(): void
    {
        Notification::fake();
        Event::fake([BusinessRegistered::class]);
        $this->seed(RolePermissionSeeder::class);

        $type = BusinessType::factory()->forType('minimarket')->create();
        $provisioningMock = Mockery::mock(OutletProvisioningService::class);
        $provisioningMock->shouldReceive('provisionAll')->once()->andReturnNull();
        $this->app->instance(OutletProvisioningService::class, $provisioningMock);

        // Pre-existing employee in another business
        $otherBusiness = Business::create([
            'name' => 'Previous Employer',
            'owner_name' => 'Old Boss',
            'email' => 'boss_'.uniqid().'@test.test',
            'phone' => '081122334455',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $employeeEmail = 'budi.barista@test.test';
        $employeePhone = '081234567899';

        User::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Budi Barista',
            'email' => $employeeEmail,
            'phone' => $employeePhone,
            'password' => bcrypt('password123'),
            'is_root_user' => false,
        ]);

        // Budi now starts his own coffee shop
        $payload = [
            'name' => 'Kopi Budi Senja',
            'owner_name' => 'Budi Mandiri',
            'outlet_name' => 'Kedai Pusat',
            'email' => $employeeEmail,
            'phone' => $employeePhone,
            'business_type_id' => $type->id,
            'password' => 'kopi123456',
            'password_confirmation' => 'kopi123456',
        ];

        $response = $this->post(route('register.store'), $payload);

        $response->assertRedirect(route('overview'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::REGISTER_SUCCESS);

        // Verify Budi is authenticated as root user of new business
        $this->assertTrue(Auth::guard('business')->check());
        $newRoot = Auth::guard('business')->user();
        $this->assertEquals($employeeEmail, $newRoot->email);
        $this->assertTrue((bool) $newRoot->is_root_user);
        $this->assertNotEquals($otherBusiness->id, $newRoot->business_id);
    }

    public function test_existing_root_owner_cannot_register_new_business_with_same_email(): void
    {
        $type = BusinessType::factory()->forType('minimarket')->create();

        $existingOwnerEmail = 'owner.active@test.test';

        $existingBusiness = Business::create([
            'name' => 'Active Business',
            'owner_name' => 'Active Owner',
            'email' => $existingOwnerEmail,
            'phone' => '081999888777',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        User::create([
            'business_id' => $existingBusiness->id,
            'name' => 'Active Owner',
            'email' => $existingOwnerEmail,
            'phone' => '081999888777',
            'password' => bcrypt('password123'),
            'is_root_user' => true,
        ]);

        $payload = [
            'name' => 'Second Business',
            'owner_name' => 'Active Owner',
            'outlet_name' => 'Second Outlet',
            'email' => $existingOwnerEmail,
            'phone' => '081999888777',
            'business_type_id' => $type->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.store'), $payload);

        $response->assertSessionHasErrors(['email', 'phone']);
    }
}
