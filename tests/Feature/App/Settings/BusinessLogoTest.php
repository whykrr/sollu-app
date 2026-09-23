<?php

namespace Tests\Feature\App\Settings;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Helpers\SummaryUser;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BusinessLogoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [resource_path('js/Pages/App')]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Logo Test Business',
            'owner_name' => 'Logo Owner',
            'email' => 'business_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => ['active_features' => array_column(FeatureEnum::cases(), 'value')],
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Business Owner User',
            'email' => 'owner_'.uniqid().'@test.test',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_root_user' => true,
        ]);

        setPermissionsTeamId($this->business->id);
        Permission::findOrCreate(PermissionEnum::BUSINESS_VIEW->value, 'business');
        Permission::findOrCreate(PermissionEnum::BUSINESS_UPDATE->value, 'business');

        $this->user->givePermissionTo(PermissionEnum::BUSINESS_VIEW->value);
        $this->user->givePermissionTo(PermissionEnum::BUSINESS_UPDATE->value);
    }

    public function test_user_can_view_business_page_with_logo_url(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/business");

        $response->assertStatus(200);
        $this->assertArrayHasKey('logo_url', $this->business->toArray());
    }

    public function test_user_can_upload_business_logo(): void
    {
        Storage::fake();

        // Warm cache
        SummaryUser::make($this->user)->cached();
        $this->assertTrue(Cache::has("auth:user:{$this->user->id}:summary"));

        $file = UploadedFile::fake()->image('logo.png', 300, 300);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/business/logo", [
                'logo' => $file,
            ]);

        $response->assertRedirect();
        $this->business->refresh();

        $this->assertNotNull($this->business->logo);
        $this->assertNotNull($this->business->logo_url);
        Storage::assertExists($this->business->logo);

        // Cache should be cleared
        $this->assertFalse(Cache::has("auth:user:{$this->user->id}:summary"));

        // SummaryUser should include logo and logo_url
        $this->user->refresh();
        $summary = SummaryUser::make($this->user)->cached();
        $this->assertArrayHasKey('logo', $summary['business']);
        $this->assertArrayHasKey('logo_url', $summary['business']);
        $this->assertEquals($this->business->logo, $summary['business']['logo']);
        $this->assertEquals($this->business->logo_url, $summary['business']['logo_url']);
    }

    public function test_user_can_remove_business_logo(): void
    {
        Storage::fake();

        $file = UploadedFile::fake()->image('logo.png', 300, 300);
        $path = $file->store('business/image');

        $this->business->update(['logo' => $path]);
        Storage::assertExists($path);

        // Warm cache
        SummaryUser::make($this->user)->cached();

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/business/logo", []);

        $response->assertRedirect();
        $this->business->refresh();

        $this->assertNull($this->business->logo);
        $this->assertNull($this->business->logo_url);
        Storage::assertMissing($path);
        $this->assertFalse(Cache::has("auth:user:{$this->user->id}:summary"));
    }

    public function test_upload_logo_validation_fails_for_non_image(): void
    {
        Storage::fake();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/business/logo", [
                'logo' => $file,
            ]);

        $response->assertSessionHasErrors('logo');
    }
}
