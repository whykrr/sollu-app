<?php

declare(strict_types=1);

namespace Tests\Feature\App\User;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);

        $this->seed(DatabaseSeeder::class);
    }

    protected function createBusinessAndUser(string $email, string $password = 'password123', string $businessName = 'Toko A', bool $isRoot = true): array
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => $businessName,
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.test',
            'phone' => '08'.random_int(100000000, 999999999),
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'User '.$businessName,
            'email' => $email,
            'password' => $password,
            'is_root_user' => $isRoot,
        ]);

        return [$business, $user];
    }

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('User/Login'));
    }

    public function test_user_can_login_with_valid_credentials_single_business(): void
    {
        [$business, $user] = $this->createBusinessAndUser('single.user@test.test', 'secret123', 'Bisnis Tunggal');

        $response = $this->post(route('login.attempt'), [
            'email' => 'single.user@test.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('overview'));
        $this->assertTrue(Auth::guard('business')->check());
        $this->assertEquals($user->id, Auth::guard('business')->id());
        $this->assertEquals($business->id, Auth::guard('business')->user()->business_id);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $this->createBusinessAndUser('user@test.test', 'correct_password');

        $response = $this->post(route('login.attempt'), [
            'email' => 'user@test.test',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('business')->check());
    }

    public function test_cashier_without_password_cannot_login_to_web(): void
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Bisnis POS',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.test',
            'phone' => '081234567891',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        // Cashier user with NULL password
        User::create([
            'business_id' => $business->id,
            'name' => 'Kasir Offline',
            'email' => 'kasir.pos@test.test',
            'password' => null,
            'pin' => '123456',
            'is_root_user' => false,
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'kasir.pos@test.test',
            'password' => 'anything',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('business')->check());
    }

    public function test_multi_business_user_sees_select_business_page_when_logging_in(): void
    {
        $sharedEmail = 'multibiz@test.test';
        $sharedPassword = 'sharedPassword123';

        [$businessA, $userA] = $this->createBusinessAndUser($sharedEmail, $sharedPassword, 'Bisnis Kopi A', true);
        [$businessB, $userB] = $this->createBusinessAndUser($sharedEmail, $sharedPassword, 'Bisnis Roti B', false);

        setPermissionsTeamId($businessB->id);
        $role = Role::firstOrCreate(
            ['business_id' => $businessB->id, 'name' => 'cashier', 'guard_name' => 'business'],
            ['label' => 'Kasir Roti', 'is_default' => false]
        );
        $userB->assignRole($role);

        $response = $this->post(route('login.attempt'), [
            'email' => $sharedEmail,
            'password' => $sharedPassword,
        ]);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('User/SelectBusiness')
            ->where('email', $sharedEmail)
            ->has('businesses', 2)
            ->where('businesses.0.name', 'Bisnis Kopi A')
            ->where('businesses.0.role', 'Pemilik Usaha')
            ->where('businesses.1.name', 'Bisnis Roti B')
            ->where('businesses.1.role', 'Kasir Roti')
        );

        // Not yet authenticated until a specific business is selected
        $this->assertFalse(Auth::guard('business')->check());
    }

    public function test_multi_business_user_can_login_with_selected_business_id(): void
    {
        $sharedEmail = 'multibiz@test.test';
        $sharedPassword = 'sharedPassword123';

        [$businessA, $userA] = $this->createBusinessAndUser($sharedEmail, $sharedPassword, 'Bisnis Kopi A', true);
        [$businessB, $userB] = $this->createBusinessAndUser($sharedEmail, $sharedPassword, 'Bisnis Roti B', false);

        // Submit with specific business_id for Business B
        $response = $this->post(route('login.attempt'), [
            'email' => $sharedEmail,
            'password' => $sharedPassword,
            'business_id' => $businessB->id,
        ]);

        $response->assertRedirect(route('overview'));
        $this->assertTrue(Auth::guard('business')->check());
        $this->assertEquals($userB->id, Auth::guard('business')->id());
        $this->assertEquals($businessB->id, Auth::guard('business')->user()->business_id);
    }

    public function test_user_with_one_cashier_account_and_one_owner_account_logs_directly_into_owner_business(): void
    {
        $sharedEmail = 'mixed@test.test';

        // 1. Business A: user is cashier without password
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );
        $businessA = Business::create([
            'name' => 'Toko Kasir A',
            'owner_name' => 'Owner A',
            'email' => 'owner_a_'.uniqid().'@test.test',
            'phone' => '081234567888',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);
        User::create([
            'business_id' => $businessA->id,
            'name' => 'Kasir Toko A',
            'email' => $sharedEmail,
            'password' => null,
            'is_root_user' => false,
        ]);

        // 2. Business B: user is owner with password
        [$businessB, $userB] = $this->createBusinessAndUser($sharedEmail, 'ownerPass123', 'Toko Pemilik B', true);

        // Attempt login with owner password
        $response = $this->post(route('login.attempt'), [
            'email' => $sharedEmail,
            'password' => 'ownerPass123',
        ]);

        // Should directly login into Business B without prompting select business
        $response->assertRedirect(route('overview'));
        $this->assertTrue(Auth::guard('business')->check());
        $this->assertEquals($userB->id, Auth::guard('business')->id());
        $this->assertEquals($businessB->id, Auth::guard('business')->user()->business_id);
    }

    public function test_user_can_logout(): void
    {
        [$business, $user] = $this->createBusinessAndUser('user.logout@test.test', 'password123');

        $this->actingAs($user, 'business');

        $response = $this->delete(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('business')->check());
    }
}
