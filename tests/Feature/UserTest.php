<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\V1_0_Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */

    public function test_create_user_merchant(): void
    {
        $this->seed(V1_0_Seeder::class);

        /**
         * @var User $user
         */
        $user     = User::firstOrFail();
        $response = $this->actingAs($user)->post('/users', [
            'name'       => 'Laura',
            'email'      => 'laura@gmail.com',
            'role'       => 'cashier',
            'outlet_ids' => [$user->merchant->outlets->first()->id],
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'email' => 'laura@gmail.com',
        ]);
    }
    public function test_update_user_merchant(): void
    {
        $this->seed(V1_0_Seeder::class);

        /**
         * @var User $user
         */
        $user       = User::whereEmail('sollu.resto@email.com')->firstOrFail();
        $userUpdate = User::whereEmail('manager.sollu.resto@email.com')->firstOrFail();

        $response = $this->actingAs($user)->put("/users/{$userUpdate->id}", [
            'name'       => 'Cashier Store',
            'email'      => 'cashier@gmail.com',
            'role'       => 'cashier',
            'outlet_ids' => [$user->merchant->outlets->first()->id],
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'email' => 'cashier@gmail.com',
        ]);
    }

    public function test_delete_user_merchant(): void
    {
        $this->seed(V1_0_Seeder::class);

        /**
         * @var User $user
         */
        $user       = User::whereEmail('sollu.resto@email.com')->firstOrFail();
        $userDelete = User::whereEmail('manager.sollu.resto@email.com')->firstOrFail();

        $response = $this->actingAs($user)->delete("/users/{$userDelete->id}");

        $response->assertRedirect('/users');
        $this->assertSoftDeleted('users', [
            'id' => $userDelete->id,
        ]);
    }

    public function test_restore_user_merchant(): void
    {
        $this->seed(V1_0_Seeder::class);

        /**
         * @var User $user
         */
        $user        = User::whereEmail('sollu.resto@email.com')->firstOrFail();
        $userRestore = User::whereEmail('manager.sollu.resto@email.com')->firstOrFail();

        $userRestore->delete();

        $response = $this->actingAs($user)->put("/users/{$userRestore->id}/restore");

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $userRestore->id,
        ]);
    }

    public function test_purge_user_merchant(): void
    {
        $this->seed(V1_0_Seeder::class);

        /**
         * @var User $user
         */
        $user        = User::whereEmail('sollu.resto@email.com')->firstOrFail();
        $userRestore = User::whereEmail('manager.sollu.resto@email.com')->firstOrFail();

        $response = $this->actingAs($user)->delete("/users/{$userRestore->id}/purge");

        $response->assertRedirect('/users');
        $this->assertDatabaseMissing('users', [
            'id' => $userRestore->id,
        ]);
    }
}
