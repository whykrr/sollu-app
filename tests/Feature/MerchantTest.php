<?php

namespace Tests\Feature;

use Auth;
use Database\Seeders\V1_0_Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('http://sollu.test/login');
        $response->assertStatus(200);
        $response->assertSee('Login');
    }

    public function test_register_page_is_accessible(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Register');
    }

    public function test_user_register(): void
    {
        $this->seed(V1_0_Seeder::class);
        $response = $this->post('/register', [
            'name' => 'PizzaHub',
            'owner_name' => 'Samuel',
            'outlet_name' => 'PizzaHub Jakarta',
            'email' => 'pizzahub.fnb@gmail.com',
            'phone' => '6282123444676',
            'address' => 'Jakarta',
            'merchant_type_id' => 1,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'email' => 'pizzahub.fnb@gmail.com',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->seed(V1_0_Seeder::class);
        $user = \App\Models\User::first();

        $this->assertNotNull($user);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertNotNull(Auth::user());
        $this->assertAuthenticatedAs($user, 'merchant');
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->seed(V1_0_Seeder::class);
        $user = \App\Models\User::first();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
