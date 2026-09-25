# Sollu App Testing & Quality Assurance Standards

Standar pengujian otomatis (*Automated Testing*), arsitektur piramida 5-layer, protokol konfirmasi skenario (*Open Question Protocol*), dan kriteria penyelesaian (*Definition of Done*) pada **Sollu App**.

---

## 1. Testing Pyramid & 5-Layer Strategy

Sollu App membagi strategi pengujian menjadi lima tingkatan terisolasi:

```
                    ▲
                   / \
                  / E2E \       Layer 4: Browser/E2E Testing (Laravel Dusk - Real User Journey)
                 /───────\
                / Regress \     Layer 5: Regression Testing (Bug Reproduction & Prevention)
               /───────────\
              / Integration \   Layer 3: Cross-Domain / Multi-Service Orchestration
             /───────────────\
            /  Feature Test   \ Layer 2: 1 Endpoint/Feature + DB + Tenant Isolation + RBAC
           /───────────────────\
          /      Unit Test      \ Layer 1: Pure Logic, Calculations, Enums (No DB)
         /───────────────────────\
```

| Layer | Fokus Pengujian | Dependensi DB | Contoh Kasus | Lokasi Direktori |
| :--- | :--- | :---: | :--- | :--- |
| **1. Unit Test** | Logika kecil, formula & murni terisolasi | ❌ No DB | Kalkulasi diskon, split tax, rumus HPP FIFO/Moving Average, Enums integrity | `tests/Unit/` |
| **2. Feature Test** | 1 HTTP Endpoint / Feature | ✅ SQLite DB | FormRequest validation, Controller CRUD, Tenant Isolation (`business_id`), Spatie `v-can` & Feature Gating `v-feature` | `tests/Feature/` |
| **3. Integration Test** | Service Layer, Jobs & Multi-Service | ✅ SQLite DB | Service mutasi stok, alur Checkout POS $\rightarrow$ Potong Stok FIFO $\rightarrow$ Cash Drawer Log $\rightarrow$ Dispatch Notification | `tests/Integration/` |
| **4. Browser/E2E Test** | Alur interaksi user nyata | ✅ Browser Engine | Login $\rightarrow$ POS $\rightarrow$ Buka drawer `<PopUpPage>` $\rightarrow$ Submit footer $\rightarrow$ Toast feedback | `tests/Browser/` |
| **5. Regression Test** | Pencegahan *bug recurrence* | ✅ Sesuai kasus | Bug reproduksi dari issue production (misal: desimal rounding return PO, concurrent locking) | `tests/Regression/` |

---

## 2. Detail Implementasi Setiap Layer

### 2.1. Layer 1: Unit Test (Pure Isolated Logic)
- Khusus untuk logika komputasi murni tanpa state (*pure stateless logic*), DTO, helper, dan PHP Backed Enum.
- Dilarang menyentuh database fisik maupun in-memory SQLite (tanpa `RefreshDatabase`).
- Dilarang membuat mock Eloquent query builder yang rumit (*anti-brittle*).
- Gunakan `PHPUnit\Framework\TestCase` standar untuk kecepatan eksekusi maksimum ($< 1\text{ms}$).

```php
namespace Tests\Unit\Calculations;

use PHPUnit\Framework\TestCase;
use App\Support\TaxCalculator;

class TaxCalculatorTest extends TestCase
{
    public function test_calculates_tax_and_subtotal_correctly(): void
    {
        $calculator = new TaxCalculator();
        $result = $calculator->calculate(subtotal: 100_000, discountPercentage: 10, taxRate: 11);

        $this->assertSame(90_000, $result->subtotalAfterDiscount);
        $this->assertSame(9_900, $result->taxAmount);
        $this->assertSame(99_900, $result->grandTotal);
    }
}
```

### 2.2. Layer 2: Feature Test (HTTP Boundary, Auth & Tenant Isolation)
- Ditempatkan di `tests/Feature/`. Menguji rute HTTP, otorisasi RBAC, SaaS Feature Gating, CSRF, dan integritas multi-tenant.
- Wajib memverifikasi bahwa tenant A tidak dapat mengakses/memanipulasi data tenant B (`HTTP 403 / 404`).

```php
namespace Tests\Feature\App\Product;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_product_scoped_to_current_business(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('app.products.store'), [
            'name' => 'Kopi Arabika',
            'base_price' => 25000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'name' => 'Kopi Arabika',
            'business_id' => $user->business_id,
        ]);
    }
}
```

### 2.3. Layer 3: Integration & Service Test (Real State Business Logic & Orchestration)
- Ditempatkan di `tests/Integration/` atau `tests/Feature/Services/`.
- Menguji Domain Service Layer, Jobs, dan orkestrasi mutasi multi-tabel **menggunakan database in-memory nyata (`RefreshDatabase`)**.
- **Dilarang me-mocking query Eloquent/Model.**
- Gunakan **Laravel Fakes resmi** (`Event::fake()`, `Queue::fake()`, `Notification::fake()`, `Storage::fake()`) untuk side-effects eksternal.

```php
namespace Tests\Integration\Pos;

use Tests\TestCase;
use App\Services\App\Pos\PosCheckoutService;
use App\Notifications\TransactionCompletedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PosCheckoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_deducts_inventory_and_logs_cash_drawer(): void
    {
        Notification::fake();

        $service = app(PosCheckoutService::class);
        $order = $service->processOrder($orderPayload, $cashierUser);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('inventory_movements', ['reference_id' => $order->id, 'qty' => -2]);
        $this->assertDatabaseHas('shift_logs', ['amount' => $order->total_amount]);

        Notification::assertSentTo($cashierUser, TransactionCompletedNotification::class);
    }
}
```

### 2.4. Layer 4: Browser / E2E Test (Laravel Dusk)
- Ditempatkan di `tests/Browser/`.
- Memvalidasi alur antarmuka frontend riil (Vue 3 / Inertia SPA DOM, drawer `<PopUpPage>`, Form fields, toast notifikasi, zero-shadow layout).
- Dilarang ada uncaught JavaScript error di browser console.

### 2.5. Layer 5: Regression Test (Bug Prevention)
- Ditempatkan di `tests/Regression/`.
- Dibuat saat mereproduksi laporan bug (*bug report* / issue production) sebelum perbaikan kode dilakukan.
- Format penamaan menyertakan nomor tiket / issue (misal `Issue402DecimalGoodsReceiptVoidTest.php`).

---

## 3. Prinsip Penegakan Uji Target 0% Error Production

1. **Anti-Brittle Mocking:** Model dan Query Builder selalu diuji terhadap database SQLite In-Memory nyata, bukan mock tiruan.
2. **Multi-Tenant Isolation Verification:** Setiap test mutasi wajib memverifikasi bahwa data scoped ke tenant terkait dan terisolasi dari tenant lain.
3. **Database Transaction & Atomicity Guard:** Alur multi-tabel (`DB::transaction`) wajib diuji jalur gagalnya (*exception path*) untuk membuktikan rollback 100%.
4. **Boundary & Precision Testing:** Menguji nilai ekstrem ($0$, minus, pembagian nol, desimal presisi tinggi pada nominal uang dan stok).
5. **Accurate Side-Effect Assertions:** Memverifikasi payload dan penerima pada notifikasi, event, dan queued job.

---

## 4. Protokol Wajib: Open Question Skenario Pengujian (Test Case Alignment)

Setiap agen atau pengembang yang melakukan perancangan implementasi fitur baru (*build*) maupun pengembangan (*enhancement*) **WAJIB** menyajikan dan mengonfirmasikan matriks skenario test yang direncanakan kepada user/stakeholder.

### Format Matriks Konfirmasi Test Case:
1. **Happy Path:** Alur normal dengan variasi input standar yang valid.
2. **Edge Cases:** Nilai batas (nilai 0, stok habis, desimal presisi tinggi, multi-satuan konversi UOM).
3. **Failure & Business Exception Path:** Saldo tidak mencukupi, status transaksi invalid/terkunci, otorisasi ditolak.
4. **Tenant Isolation & Security:** Verifikasi pencegahan kebocoran data antar `business_id` / `outlet_id`.
5. **Cross-Domain Side Effects:** Pengecekan ledger mutasi inventory, log audit trail, dan broadcast notifikasi.

---

## 5. Perintah Eksekusi Pengujian

```bash
# Menjalankan seluruh test suite dengan output ringkas
php artisan test --compact

# Menjalankan test suite per layer spesifik
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Integration
php artisan test --testsuite=Regression

# Menjalankan test spesifik berdasarkan filter
php artisan test --compact --filter=TaxCalculatorTest

# Menjalankan Laravel Dusk E2E
php artisan dusk
```

---

## 6. Definition of Done (DoD) & Pre-Commit Checklist

Sebelum menyelesaikan tugas atau membuat commit:

- [ ] **Test Alignment Confirmed:** Skenario uji (Happy path, edge cases, exceptions, tenant isolation) telah diselaraskan.
- [ ] **All Test Layers Passed:** Seluruh automated test (Unit, Feature, Integration, Regression) lulus (`100% passing`).
- [ ] **No Hardcoded Strings:** Pesan controller merujuk ke `App\Constants\*` dan status merujuk ke Enum PHP.
- [ ] **Tenant Scoped:** Seluruh query dan mutasi data terisolasi oleh `business_id` / `outlet_id`.
- [ ] **UI & Console Clean:** Komponen Vue diverifikasi fungsional dan visual (bebas error Vite & console log bersih).
- [ ] **PHP Formatted:** `vendor/bin/pint --dirty` dijalankan dengan sukses.
- [ ] **Frontend Formatted & Linted:** `npm run lint` dan `npm run format` sukses tanpa error.
- [ ] **Database Rollback Symmetric:** Verifikasi `migrate -> rollback -> migrate` sukses jika ada migration baru (Rule 08).
