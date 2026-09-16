# Sollu App Testing & Quality Assurance Standards

Standar pengujian otomatis (*Automated Testing*), pengujian integrasi web (*E2E Testing*), dan kriteria penyelesaian (*Definition of Done*) pada **Sollu App**.

---

## 1. Testing Pyramid & Strategy

Sollu App membagi strategi pengujian menjadi tiga tingkatan:

```
                    ▲
                   / \
                  /   \
                 / E2E \       Web Integration & Visual Testing (browsermcp / Dusk)
                /───────\
               / Feature \     HTTP Boundary, Multi-Tenant Isolation & Auth Tests
              /───────────\
             / Service Unit\   100% Mocking, In-Memory SQLite, Branch Coverage
            /───────────────\
```

---

## 2. Service Layer Unit Testing (100% Mocking & In-Memory SQLite)

Setiap pembuatan atau pembaruan **Service Class** **WAJIB** disertai Unit Test di `tests/Unit/Services/...`.

### 2.1. Aturan Pengujian Unit Service
1. **Pure In-Memory SQLite:** Dilarang menyentuh database fisik PostgreSQL. Selalu gunakan koneksi `sqlite:memory` dan trait `RefreshDatabase`.
2. **100% Mocking:** Seluruh dependensi eksternal (Service lain, Notification, Event Dispatcher, Payment Gateway, External Client) wajib di-mock menggunakan **Mockery**.
3. **100% Code Coverage:** Uji seluruh percabangan skenario:
   - *Happy Path* (Skenario sukses normal)
   - *Validation / Business Exception Path* (Skenario gagal, saldo tidak cukup, status tidak valid)
   - *Edge Cases* (Data kosong, nilai batas, desimal ekstrem)

### 2.2. Struktur dan Pola Penulisan Test
Struktur direktori test mencerminkan namespace class asli:
- Target: `app/Services/App/Inventory/StockAdjustmentService.php`
- Test: `tests/Unit/Services/App/Inventory/StockAdjustmentServiceTest.php`

```php
namespace Tests\Unit\Services\App\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Models\Business;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockAdjustment;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Inventory\StockAdjustmentService;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StockAdjustmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockAdjustmentService $service;
    protected $auditLogServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditLogServiceMock = Mockery::mock(AuditLogService::class);
        $this->app->instance(AuditLogService::class, $this->auditLogServiceMock);

        $this->service = new StockAdjustmentService($this->auditLogServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_create_stock_adjustment_successfully(): void
    {
        $business = Business::factory()->create();
        $outlet = Outlet::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $item = InventoryItem::factory()->create(['business_id' => $business->id]);

        $this->auditLogServiceMock
            ->shouldReceive('log')
            ->once();

        $data = [
            'outlet_id' => $outlet->id,
            'reason'    => AdjustmentReason::DAMAGED->value,
            'items'     => [
                ['inventory_item_id' => $item->id, 'qty' => 5],
            ],
        ];

        $adjustment = $this->service->create($data, $user);

        $this->assertInstanceOf(StockAdjustment::class, $adjustment);
        $this->assertEquals(AdjustmentStatus::Draft, $adjustment->status);
        $this->assertDatabaseHas('stock_adjustments', [
            'id'          => $adjustment->id,
            'business_id' => $business->id,
        ]);
    }
}
```

---

## 3. Feature & HTTP Boundary Testing

Ditempatkan di `tests/Feature/`. Menguji rute HTTP, otorisasi RBAC, SaaS Feature Gating, CSRF, dan integritas data on-demand.

### 3.1. Area Pengujian Feature Wajib
- **Tenant Isolation:** Memastikan user dari Bisnis A tidak dapat mengakses atau memanipulasi data milik Bisnis B (`HTTP 403 / 404`).
- **RBAC Permission Gate:** Memastikan user tanpa permission yang sesuai ditolak (`HTTP 403`).
- **Feature Plan Gating:** Memastikan tenant dengan paket basic ditolak saat mengakses fitur pro (`is_feature_locked: true`).
- **On-Demand Data Loading:** Memastikan response payload `index()` ringan dan tidak mengandung relasi berat (`OnDemandDataLoadingTest`).

---

## 4. Web Integration & E2E Testing (`browsermcp`)

Digunakan untuk memvalidasi alur UI frontend (Vue 3 / Inertia) secara visual dan interaktif.

### 4.1. Standard Workflow E2E Testing
1. **Navigasi & Autentikasi (`browser_navigate`, `browser_type`, `browser_click`):**
   - Buka `http://app.sollu.test/login`.
   - Masukkan kredensial pengujian (`sollu.mart@email.com` / `password`).
2. **Pengujian Alur Side Drawer & Form:**
   - Verifikasi tabel utama pada `<MainPage>`.
   - Buka drawer `<PopUpPage>` dengan klik tombol tambah/edit.
   - Isi field form `@/Components/Form/`.
   - Submit via tombol aksi sticky footer `#popUpFooter`.
3. **Verifikasi DOM & Screenshot:**
   - Ambil `browser_snapshot` untuk verifikasi elemen accessibility tree.
   - Ambil `browser_screenshot` untuk memastikan tidak ada layout patah/rusak dan toast notifikasi muncul.
4. **Inspeksi Console Logs (`browser_get_console_logs`):**
   - **WAJIB** periksa logs browser. Tidak boleh ada JavaScript uncaught exception atau error 500/422 yang unhandled.

---

## 5. Menjalankan Pengujian

```bash
# Menjalankan seluruh test suite dengan output ringkas
php artisan test --compact

# Menjalankan test unit spesifik
php artisan test tests/Unit/Services/App/Inventory/StockAdjustmentServiceTest.php

# Menjalankan PHPUnit langsung dengan filter
vendor/bin/phpunit --filter=test_can_create_stock_adjustment_successfully

# Menjalankan Laravel Dusk (jika ada)
php artisan dusk
```

---

## 6. Definition of Done (DoD) & Pre-Commit Checklist

Sebelum menyelesaikan tugas atau membuat commit:

- [ ] **Unit Tests Passed:** Seluruh service layer unit test dibuat/diperbarui dan lulus (`100% Mocking`).
- [ ] **Feature Tests Passed:** Rute HTTP, tenant isolation, dan proteksi permission teruji.
- [ ] **No Hardcoded Strings:** Pesan controller merujuk ke `App\Constants\*` dan status merujuk ke Enum PHP.
- [ ] **On-Demand Loading Followed:** Props `index()` ringan, detail dimuat async saat drawer dibuka.
- [ ] **UI Verified:** Komponen Vue diverifikasi visual & console log bersih via `browsermcp`.
- [ ] **No Dead Code:** Komentar kode lama, import tidak terpakai, dan method yatim telah dibersihkan.
- [ ] **PHP Formatted:** `composer run format` (`vendor/bin/pint`) dijalankan dengan sukses (method chaining multiline terjaga).
- [ ] **Frontend Formatted & Linted:** `npm run format`, `npm run lint`, dan `npm run build` sukses tanpa error.
- [ ] **API Docs Updated:** Perubahan endpoint diperbarui di `docs/openapi.yaml` dan `docs/postman_collection.json`.
