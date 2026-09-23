<?php

namespace Tests\Feature\App\Settings;

use App\Helpers\SummaryUser;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountPhotoTest extends TestCase
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
        ]);
    }

    public function test_user_can_view_account_page_with_photo_url(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/settings/account");

        $response->assertStatus(200);
        $this->assertArrayHasKey('photo_url', $this->user->toArray());
    }

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake();

        // Warm cache
        SummaryUser::make($this->user)->cached();
        $this->assertTrue(Cache::has("auth:user:{$this->user->id}:summary"));

        $file = UploadedFile::fake()->image('avatar.png', 200, 200);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/account/photo", [
                'photo' => $file,
            ]);

        $response->assertRedirect();
        $this->user->refresh();

        $this->assertNotNull($this->user->photo);
        $this->assertNotNull($this->user->photo_url);
        Storage::assertExists($this->user->photo);

        // Cache should be cleared
        $this->assertFalse(Cache::has("auth:user:{$this->user->id}:summary"));
    }

    public function test_user_can_remove_profile_photo(): void
    {
        Storage::fake();

        $file = UploadedFile::fake()->image('avatar.png', 200, 200);
        $path = $file->store('user/photo');

        $this->user->update(['photo' => $path]);
        Storage::assertExists($path);

        // Warm cache
        SummaryUser::make($this->user)->cached();

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/settings/account/photo");

        $response->assertRedirect();
        $this->user->refresh();

        $this->assertNull($this->user->photo);
        $this->assertNull($this->user->photo_url);
        Storage::assertMissing($path);
        $this->assertFalse(Cache::has("auth:user:{$this->user->id}:summary"));
    }

    public function test_upload_photo_validation_fails_for_non_image(): void
    {
        Storage::fake();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/settings/account/photo", [
                'photo' => $file,
            ]);

        $response->assertSessionHasErrors('photo');
    }
}
