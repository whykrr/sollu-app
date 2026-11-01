<?php

namespace Tests\Feature\App\Settings;

use App\Constants\FlashDataVariable;
use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Helpers\SummaryUser;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => []]
        );

        $this->business = Business::create([
            'name' => 'Account Test Business',
            'owner_name' => 'Account Owner',
            'email' => 'business_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [],
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Test Account User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_root_user' => true,
        ]);
    }

    public function test_user_can_view_account_page(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/account");

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Account/Detail')
                ->has('profile')
            );
    }

    public function test_user_can_update_profile_info(): void
    {
        $payload = [
            'id' => $this->user->id,
            'name' => 'Updated Account Name',
            'email' => $this->user->email,
            'phone' => '081299990000',
        ];

        $response = $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/settings/account")
            ->put("http://{$this->appDomain}/settings/account", $payload);

        $response->assertRedirect("http://{$this->appDomain}/settings/account");
        $this->user->refresh();

        $this->assertSame('Updated Account Name', $this->user->name);
        $this->assertSame('081299990000', $this->user->phone);
    }

    public function test_user_can_change_password(): void
    {
        $payload = [
            'current_password' => 'password123',
            'new_password' => 'newsecretpassword123',
            'new_password_confirmation' => 'newsecretpassword123',
        ];

        $response = $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/settings/account")
            ->put("http://{$this->appDomain}/settings/account/password", $payload);

        $response->assertRedirect("http://{$this->appDomain}/settings/account");
        $this->user->refresh();

        $this->assertTrue(Hash::check('newsecretpassword123', $this->user->password));
    }

    public function test_user_can_change_pos_pin_successfully(): void
    {
        // Warm caches
        SummaryUser::make($this->user)->cached();
        Cache::put("auth:user:{$this->user->id}:info", ['dummy' => true]);

        $this->assertTrue(Cache::has("auth:user:{$this->user->id}:summary"));
        $this->assertTrue(Cache::has("auth:user:{$this->user->id}:info"));

        $auditMock = $this->mock(ActivityLoggerInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('log')
                ->once()
                ->withArgs(function ($module, $action) {
                    return $module === AuditModuleEnum::AUTH->value
                        && $action === 'account.pin_updated';
                });
        });

        $payload = [
            'pin' => '852963',
            'pin_confirmation' => '852963',
        ];

        $response = $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/settings/account")
            ->put("http://{$this->appDomain}/settings/account/pin", $payload);

        $response->assertRedirect("http://{$this->appDomain}/settings/account")
            ->assertSessionHas(FlashDataVariable::SUCCESS->value, 'PIN kasir berhasil diperbarui.');

        $this->user->refresh();
        $this->assertTrue(Hash::check('852963', $this->user->pin));

        // Caches must be invalidated
        $this->assertFalse(Cache::has("auth:user:{$this->user->id}:summary"));
        $this->assertFalse(Cache::has("auth:user:{$this->user->id}:info"));
    }

    public function test_change_pin_validations(): void
    {
        // 1. Unconfirmed PIN
        $response = $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/settings/account")
            ->put("http://{$this->appDomain}/settings/account/pin", [
                'pin' => '123456',
                'pin_confirmation' => '654321',
            ]);
        $response->assertSessionHasErrors('pin');

        // 2. PIN less than 6 digits
        $response = $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/settings/account")
            ->put("http://{$this->appDomain}/settings/account/pin", [
                'pin' => '12345',
                'pin_confirmation' => '12345',
            ]);
        $response->assertSessionHasErrors('pin');

        // 3. Non numeric PIN
        $response = $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/settings/account")
            ->put("http://{$this->appDomain}/settings/account/pin", [
                'pin' => 'abcdef',
                'pin_confirmation' => 'abcdef',
            ]);
        $response->assertSessionHasErrors('pin');
    }
}
