<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CustomerTest extends DuskTestCase
{
    /**
     * Test the CRM Customer flow.
     */
    public function testCustomerFlow(): void
    {
        $this->browse(function (Browser $browser) {
            // 1. Authentication
            $browser->visit('http://dashboard.sollu.test/bypass-auth')
                ->assertPathIs('/')
                // 2. Navigate to Customers
                ->visit('http://dashboard.sollu.test/customers')
                ->waitForText('Daftar Pelanggan', 10)
                // 3. Open Create PopUp
                ->click('.btn-highlight-main')
                ->waitForText('Nama Lengkap', 5)
                // 4. Fill Form
                ->type('input[placeholder="Masukkan nama pelanggan"]', 'Dusk Test Customer')
                ->type('input[placeholder="Contoh: 08123456789"]', '08999999999')
                ->type('input[type="email"]', 'dusk@test.com')
                ->press('Simpan')
                // 5. Verify Data Appears in Table
                ->waitForText('Dusk Test Customer', 5)
                ->assertSee('Dusk Test Customer')
                ->assertSee('08999999999')
                ->assertSee('dusk@test.com')
                // 6. Test Detail PopUp
                ->click('button[title="Detail Pelanggan"]')
                ->waitForText('Riwayat Transaksi', 5)
                ->press('Tutup');
        });
    }
}
