# Sollu App Authorization & Entitlement Architecture

Dokumentasi sistem otorisasi ganda (*Dual-Layer Authorization*) yang memisahkan antara **Hak Akses Karyawan (RBAC)** dan **Paket Berlangganan Bisnis (Feature Plan Gating)**.

---

## 1. Dual-Layer Authorization Architecture

Sollu App menerapkan dua lapisan proteksi otorisasi yang terpisah secara tegas:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           Incoming HTTP Request                         │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │
                 ┌───────────────────┴───────────────────┐
                 │ 1. SaaS Feature Plan Guard (Tenant)   │
                 │ Middleware: `plan.feature:promo_mgmt` │
                 └───────────────────┬───────────────────┘
                                     │ (Pass)
                 ┌───────────────────┴───────────────────┐
                 │ 2. User RBAC Permission Guard (User)  │
                 │ `$this->authorize('promos.create')`   │
                 └───────────────────┬───────────────────┘
                                     │ (Pass)
                                     ▼
                            Controller Execution
```

### 1.1. Perbandingan Otorisasi: RBAC vs Feature Plan

| Dimensi | Lapisan 1: User RBAC (Jabatan) | Lapisan 2: Feature Plan (Paket SaaS) |
| :--- | :--- | :--- |
| **Fokus Objek** | Akun Pengguna / Karyawan (`User`) | Bisnis / Merchant (`Business`) |
| **Tujuan** | Membatasi aksi berdasarkan wewenang kerja | Membatasi fitur berdasarkan paket langganan aktif |
| **Backend Guard** | `$this->authorize('permission.name')` | `middleware('plan.feature:feature_name')` |
| **Inertia Prop** | `props.auth.permissions` | `props.auth.features` |
| **Vue Directive** | `v-can="'permission.name'"` | `v-feature="$enums.FeatureEnum.FEATURE_NAME"` |
| **Vue Composable** | `useAuth()` (`can`, `hasRole`, `isOwner`) | `usePlanFeature()` (`hasFeature`, `requireFeature`) |
| **UI Handling** | Elemen dihilangkan dari DOM (*hidden*) | Elemen dikunci dengan tawaran upgrade (`<FeatureLock>`) |

---

## 2. Layer 1: Role-Based Access Control (RBAC)

### 2.1. Multi-Tenant Role Isolation (Team-Scoped)
- Menggunakan package `spatie/laravel-permission` v6.
- Role diisolasi per tenant menggunakan konfigurasi `teams => true` dengan `team_foreign_key = 'business_id'`.
- Tim dievaluasi otomatis saat user terautentikasi melalui `setPermissionsTeamId($user->business_id)` di `AppServiceProvider.php`.
- **Default Roles per Bisnis:**
  1. `owner`: Pemilik bisnis dengan akses penuh ke semua permission.
  2. `manager`: Manajer outlet/operasional dengan akses terbatas konfigurasi sistem.
  3. `cashier`: Staf kasir dengan akses operasional POS dan transaksi harian.

### 2.2. Permission Source of Truth (`PermissionEnum.php`)
Seluruh permission didefinisikan sebagai string berformat dot-notation di `App\Enums\PermissionEnum`:

```php
namespace App\Enums;

enum PermissionEnum: string
{
    // Settings & Outlets
    case SETTINGS_OUTLET_INDEX  = 'settings.outlets.index';
    case SETTINGS_OUTLET_CREATE = 'settings.outlets.create';
    case SETTINGS_OUTLET_UPDATE = 'settings.outlets.update';
    case SETTINGS_OUTLET_DELETE = 'settings.outlets.delete';

    // Inventory
    case INVENTORY_ITEM_INDEX   = 'inventory.items.index';
    case INVENTORY_ITEM_CREATE  = 'inventory.items.create';
    case INVENTORY_STOCK_ADJUST = 'inventory.adjustments.create';
}
```

### 2.3. Backend Authorization Enforcement
```php
// 1. Di dalam Controller Method
public function store(StoreOutletRequest $request)
{
    $this->authorize(PermissionEnum::SETTINGS_OUTLET_CREATE->value);

    // Business Logic...
}

// 2. Di dalam FormRequest
public function authorize(): bool
{
    return $this->user()->can(PermissionEnum::SETTINGS_OUTLET_CREATE->value);
}
```

> [!WARNING]
> DILARANG KERAS mengecek string nama role mentah seperti `if ($user->role === 'admin')`. Selalu gunakan evaluasi permission via `$user->can(...)` atau `$user->hasPermissionTo(...)`.

### 2.4. Frontend Authorization (`useAuth`)
```vue
<script setup>
import { useAuth } from '@/Composable/useAuth'
import { PermissionEnum } from '@/enums' // Atau via $enums

const { can, canAny, isOwner, hasRole } = useAuth()
</script>

<template>
    <!-- Directive Usage -->
    <button v-can="'settings.outlets.create'" class="btn btn-main">
        Tambah Outlet
    </button>

    <!-- Composable Usage -->
    <div v-if="can('inventory.items.create')">
        <button class="btn btn-secondary">Impor Barang</button>
    </div>
</template>
```

---

## 3. Layer 2: SaaS Feature Plan Gating

### 3.1. Master Fitur & Paket (`FeatureEnum`, `Feature`, & `SubscriptionPlan`)
- Daftar kode fitur standar terdaftar di `App\Enums\FeatureEnum` (murni sebagai string keys untuk type-safety).
- Metadata fitur lengkap (nama, deskripsi, modul, grup) disimpan di tabel database `features` via model `App\Models\Feature` dan di-seed melalui `FeatureSeeder.php`.
- Paket langganan standar didefinisikan di `App\Enums\PlanEnum` (Micro, Basic, Pro), serta mendukung Custom Plan dinamis (`is_custom: true`, `is_public: false`) pada tabel `subscription_plans`.
- Pemetaan fitur ke paket langganan dikelola melalui tabel pivot database `plan_features` (`SubscriptionPlan::systemFeatures(): BelongsToMany`), BUKAN hardcoded di PHP enum.

```php
namespace App\Enums;

enum FeatureEnum: string
{
    case INVENTORY_MANAGEMENT = 'inventory_management';
    case RECIPE_MANAGEMENT    = 'recipe_management';
    case PROMO_MANAGEMENT     = 'promo_management';
    case MULTI_OUTLET         = 'multi_outlet';
    case ADVANCED_REPORTS     = 'advanced_reports';
}
```

### 3.2. Proteksi Route Backend
```php
// routes/app/promotions.php
Route::middleware(['auth:business', 'plan.feature:' . FeatureEnum::PROMO_MANAGEMENT->value])
    ->prefix('promotions')
    ->name('promotions.')
    ->group(function () {
        Route::get('/', [PromoController::class, 'index'])->name('index');
    });
```

Jika tenant tidak memiliki akses ke fitur ini:
- **AJAX / API Request:** Mengembalikan response HTTP `403 Forbidden` dengan JSON payload `{ "message": "Fitur ini tidak tersedia pada paket Anda.", "is_feature_locked": true }`.
- **Inertia / Web Request:** Me-redirect balik (`redirect()->back()`) dengan membawa flash session `feature_locked` yang secara otomatis memicu terbukanya `<FeatureLockedModal>`.

### 3.3. Frontend Feature Gating & Lock Display

#### A. Directive `v-feature`
```html
<!-- Elemen dihilangkan jika tidak memiliki fitur -->
<button v-feature="$enums.FeatureEnum.PROMO_MANAGEMENT" class="btn btn-main">
    Buat Promo
</button>
```

#### B. Component `<FeatureLock>` & `<FeatureLockOverlay>` (Direkomendasikan)
Menampilkan preview fitur yang terkunci dengan tombol ajakan upgrade (*upsell*):
```vue
<template>
    <FeatureLock :feature="$enums.FeatureEnum.RECIPE_MANAGEMENT">
        <div class="card p-4">
            <h3>Manajemen Resep (BOM)</h3>
            <p>Atur komposisi bahan baku untuk setiap menu produk.</p>
        </div>
    </FeatureLock>
</template>
```

#### C. Composable `usePlanFeature`
```javascript
import { usePlanFeature } from '@/Composable/usePlanFeature'
import { useEnum } from '@/Composable/useEnum'

const { hasFeature, requireFeature } = usePlanFeature()
const { enums } = useEnum()

if (hasFeature(enums.FeatureEnum.PROMO_MANAGEMENT)) {
    // Jalankan logika promo
}
```

---

## 4. Alur Penambahan Fitur Baru (Checklist)

1. **Definisikan Permission RBAC:** Tambahkan case baru di `App\Enums\PermissionEnum.php`.
2. **Assign ke Default Role:** Daftarkan permission baru ke role `owner` / `manager` di `App\Services\App\Role\RoleProvisioningService.php`.
3. **Definisikan SaaS Feature (jika berbayar):** Tambahkan case di `App\Enums\FeatureEnum.php`, daftarkan metadata di `FeatureSeeder.php` (tabel `features`), lalu sinkronisasikan relasi paket di `SubscriptionPlanSeeder.php` atau Cockpit UI (tabel `plan_features`). *(Catatan: Fitur ekspor/impor diatur murni via RBAC langkah 1-2, bukan SaaS feature plan).*
4. **Jalankan Seeder Permission:**
   ```bash
   php artisan db:seed --class="Database\Seeders\Production\RolePermissionSeeder"
   php artisan sollu:normalize-rbac
   ```
5. **Pasang Proteksi:** Tambahkan `$this->authorize()` pada Controller/FormRequest dan `middleware('plan.feature:...')` pada route.
6. **Implementasi UI:** Pasang directive `v-can` atau wrapper `<FeatureLock>`.
