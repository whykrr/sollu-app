<?php

namespace Tests\Feature\App\Outlet;

use App\Helpers\SelectedOutlet;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwitchOutletTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($this->business->id);

        $this->outletA = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang A',
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang B',
            'is_active' => true,
        ]);

        $this->user->outlets()->attach([$this->outletA->id, $this->outletB->id]);
    }

    public function test_user_can_switch_to_accessible_outlet(): void
    {
        $sessionKey = SelectedOutlet::make($this->user)->getSessionKey();

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/switch-outlet/{$this->outletA->id}");

        $response->assertStatus(302);
        $this->assertEquals($this->outletA->id, session($sessionKey));
    }

    public function test_user_can_switch_to_all_outlets(): void
    {
        $sessionKey = SelectedOutlet::make($this->user)->getSessionKey();

        // First switch to outlet A
        session([$sessionKey => $this->outletA->id]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/switch-outlet/all");

        $response->assertStatus(302);
        $this->assertNull(session($sessionKey));
    }

    public function test_user_cannot_switch_to_other_tenant_outlet(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Business',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '0899999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $otherOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Outlet',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/switch-outlet/{$otherOutlet->id}");

        $response->assertStatus(403);
    }

    public function test_selected_outlet_auto_resolves_when_user_has_only_one_outlet(): void
    {
        // Detach outlet B and delete so user has only 1 outlet
        $this->user->outlets()->detach($this->outletB->id);
        $this->outletB->delete();

        $this->actingAs($this->user, 'business');

        $selected = SelectedOutlet::make($this->user)->get();

        $this->assertNotNull($selected);
        $this->assertEquals($this->outletA->id, $selected->id);
    }

    public function test_selected_outlet_handles_legacy_json_or_array_session(): void
    {
        $sessionKey = SelectedOutlet::make($this->user)->getSessionKey();

        // Legacy JSON string in session
        session([$sessionKey => json_encode(['id' => $this->outletA->id, 'name' => 'Legacy Format'])]);

        $this->actingAs($this->user, 'business');

        $selected = SelectedOutlet::make($this->user)->get();

        $this->assertNotNull($selected);
        $this->assertEquals($this->outletA->id, $selected->id);
    }

    public function test_selected_outlet_handles_invalid_corrupted_session_without_crashing(): void
    {
        $sessionKey = SelectedOutlet::make($this->user)->getSessionKey();

        // Invalid non-UUID string in session
        session([$sessionKey => 'not-a-valid-uuid-string']);

        $this->actingAs($this->user, 'business');

        $selected = SelectedOutlet::make($this->user)->get();

        // Should not crash, and should return null (or auto-resolve if only 1 outlet)
        $this->assertNull($selected);
    }
}
