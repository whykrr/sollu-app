<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IntegrationTest extends DuskTestCase
{
    /**
     * A basic end-to-end test using the bypass auth route.
     */
    public function testUserFlow()
    {
        // Ensure a user exists
        $user = User::first();
        $this->assertNotNull($user, 'No user found for login');

        $this->browse(function (Browser $browser) {
            // Visit the Valet URL and trigger bypass auth
            $browser->visit('http://dashboard.sollu.test/bypass-auth')
                ->assertPathIs('/')
                    // Now perform a sample UI interaction, e.g., open dashboard
                ->visit('http://dashboard.sollu.test/')
                ->assertSee('Dashboard')
                    // Example: click a button with text "Tambah Data"
                ->clickLink('Tambah Data')
                ->assertSee('Form')
                    // Fill a sample text field (adjust selector as needed)
                ->type('input[name="name"]', 'Test Item')
                ->press('Simpan')
                ->assertSee('Data berhasil disimpan');
        });
    }
}
