---
name: domain-access-and-feature-gating
description: >-
    Knowledge and standards for Tiered Feature Gating and Access Control in Sollu.
    Covers the 6-layer gating stack (Tenant Lifecycle, BusinessType, SaaS Plan, Personalization,
    Outlet Guards, and User RBAC), Fast-Path Caching (SummaryUser, UserPermissionCacheService),
    UI Duality (v-can vs v-feature.lock), and Zero-Code-Scan analysis formula.
    Activate whenever evaluating permissions, subscription feature locks, access control, or role policies.
---

# Domain Skill: Tiered Access Control & Feature Gating

Pedoman komprehensif sistem pembatasan fitur bertingkat (*tiered feature gating*) dan otorisasi akses (*RBAC*) di ekosistem **Sollu App** dan **Sollu POS Client**. 

Skill ini dirancang untuk **memungkinkan AI Agent menganalisa, memvalidasi, dan menyelesaikan masalah akses tanpa melakukan pemindaian menyeluruh ke seluruh berkas kode (*Zero-Codebase-Scan Strategy*)**.

---

## 1. Quick Cheatsheet & Formula Evaluasi Instan (Zero-Code-Scan)

Saat mengevaluasi apakah seorang user dapat mengakses aksi atau fitur tertentu:

$$\text{CanAccess}(user, feature, action) = \text{IsFeatureActive}(business, feature) \land \text{HasPermission}(user, action) \land \text{IsOutletOperational}(outlet)$$

### Alur Keputusan 3 Detik:

```
Apakah yang sedang dievaluasi?
  │
  ├─► Kapabilitas Toko / Modul Bisnis? (Inventori, CRM, Laporan Lanjutan, Promo)
  │     └─► Gunakan LAYER 1 - 3: SaaS Feature Gating (FeatureEnum)
  │         • Cek: Apakah feature ∈ (BusinessType ∩ Plan ∩ (Personalization || All))?
  │         • Frontend: Gunakan `v-feature.lock` (Tampilkan gembok & modal upsell)
  │         • Backend: Dilindungi oleh Route Middleware `plan.feature:feature_name`
  │
  ├─► Kewenangan Jabatan Karyawan? (Tambah produk, Void transaksi, Stock opname)
  │     └─► Gunakan LAYER 5: User RBAC (PermissionEnum)
  │         • Cek: Apakah user memiliki permission atau is_root_user = true?
  │         • Frontend: Gunakan `v-can` (Sembunyikan tombol / elemen visual)
  │         • Backend: Dilindungi oleh FormRequest `authorize()` atau Controller `$this->authorize(...)`
  │
  └─► Batas Kuota Toko atau Kondisi Kasir? (Tambah outlet, kasir tidak bisa buka meja)
        └─► Gunakan LAYER 0 & 4: Tenant Lifecycle & Operational Guards
            • Cek kuota: max_outlets di SubscriptionPlan
            • Cek freeze: outlet.is_stock_frozen
            • Cek status outlet: outlet.is_active (jika false -> 403 OUTLET_INACTIVE)
```

---

## 2. Hierarki 6 Lapisan Pembatasan (The 6-Layer Gating Stack)

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        LAYER 0: TENANT & SUBSCRIPTION LIFECYCLE                        │
│  • Status Berlangganan: SubscriptionStatus::Active ('active')                          │
│  • Masa Aktif: expired_at IS NULL atau expired_at >= now()                             │
│  • Grace Period (Tenggang): 3 hari setelah expired_at (Business::isInGracePeriod())    │
│  • Payment Wall: subscription_lock (alasan: payment_pending, trial_expired, expired)   │
│  • Pengecualian Rute Bebas Kunci: /settings/billing/*, /settings/profile/*             │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        LAYER 1: BUSINESS TYPE CAPABILITY GATING                        │
│  • Entitas Dinamis DB: Tabel business_types (bukan enum PHP)                           │
│  • Saringan Vertikal Industri: BusinessType::featureEnums() (F&B / Retail / Jasa)      │
│  • Jika modul tidak relevan untuk jenis bisnis, fitur DIBLOKIR walau paket mendukung   │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        LAYER 2: SAAS PLAN FEATURE GATING                               │
│  • Single Source of Truth Paket: SubscriptionPlan::activeFeatureEnums()                │
│  • Paket Baku (PlanEnum): Micro, Basic, Pro                                            │
│  • Relasi DB: subscription_plans <-> plan_features <-> features (FeatureEnum)          │
│  • Base Allowed Pool = BusinessType->features ∩ Plan->features                         │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        LAYER 3: MERCHANT FEATURE PERSONALIZATION                       │
│  • Syarat: Plan memuat FeatureEnum::FEATURE_PERSONALIZATION (Paket Basic ke atas)      │
│  • Konfigurasi: business.settings['active_features'] (FeatureSettingController)        │
│  • Active Pool = settings['active_features'] ∩ Base Allowed Pool                       │
│  • Default Micro Plan / Belum Diatur: Otomatis menggunakan seluruh Base Allowed Pool   │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        LAYER 4: OUTLET & DEVICE OPERATIONAL GUARDS                     │
│  • Kuota Cabang: subscription_plans.max_outlets (null = unlimited, int >= 1)           │
│  • Rekonsiliasi Downgrade: Merchant wajib memilih cabang aktif; sisa dicabut (is_active)│
│  • Stock Freeze Guard: is_stock_frozen aktif memblokir mutasi pergerakan inventori     │
│  • Outlet Inactive Guard: is_active = false memblokir akses POS (HTTP 403 OUTLET_INACTIVE)
│  • POS Device Pairing: Otentikasi OTP & hardware_fingerprint via PosDeviceAuthCache    │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        LAYER 5: USER ROLE-BASED ACCESS CONTROL (RBAC)                  │
│  • Spatie Multi-Tenant Team-Scoped Permissions: PermissionEnum (setPermissionsTeamId)  │
│  • Super-Bypass Akun Utama: is_root_user = true selalu memiliki hak penuh ['*']        │
│  • Pemisahan Kasir POS (email/password null, PIN only) vs Staf Web Backoffice          │
│  • Role Templates: RoleTemplateEnum (F&B, Retail, Jasa, Owner, Finance)                │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Matriks Kontras: User RBAC vs SaaS Feature Plan

Dilarang keras mencampuradukkan pengecekan RBAC dengan SaaS Feature Gating. Keduanya memiliki tujuan, visualisasi, dan penanganan yang sangat bertolak belakang:

| Parameter | User RBAC (Layer 5) | SaaS Feature Plan (Layer 1 - 3) |
| :--- | :--- | :--- |
| **Dimensi Pengaturan** | Kewenangan Jabatan Personal User | Kapabilitas Komersial Tenant / Bisnis |
| **Master Enum SSOT** | `PermissionEnum` (e.g. `product.create`) | `FeatureEnum` (e.g. `inventory_management`) |
| **Prinsip UI / UX** | **Silent Guard (Sembunyikan)** | **Commercial Upsell (Kunci & Ajak Upgrade)** |
| **Directive Vue** | `v-can="'permission.name'"` | `v-feature.lock="'feature_name'"` |
| **Komponen Vue** | `v-if="$can('permission.name')"` | `<FeatureLock :feature="...">` / `<FeatureLockOverlay>` |
| **Perlindungan Backend**| `FormRequest::authorize()` / Controller Gate | Route Middleware: `plan.feature:feature_name` |
| **Respon Backend (JSON)**| HTTP 403 `{ message: "This action is unauthorized." }` | HTTP 403 `{ is_feature_locked: true, feature: "..." }` |
| **Respon Backend (Web)** | HTTP 403 Exception | Redirect back dengan flash `feature_locked` |
| **Interaksi Pengguna** | Tidak ada akses, menu/tombol lenyap | Tampil overlay gembok $\rightarrow$ Klik $\rightarrow$ Buka `FeatureLockedModal.vue` |

---

## 4. Arsitektur Caching & Fast-Path Lookups (Zero-Redundant Query)

Sistem menggunakan multi-tier caching berjenjang untuk memastikan evaluasi hak akses tidak menghasilkan *query amplification* (N+1):

### A. Fast-Path Backend Middleware (`CheckPlanFeature`)
1. **Tier 1 (Fastest):** Membaca `SummaryUser::make($user)->cached()['features']`. Cache ini telah menghitung perpotongan *BusinessType*, *Plan*, dan *Merchant Personalization*.
2. **Tier 2 (Fallback):** Membaca `Business::findCached($id)->hasFeature($enum)` $\rightarrow$ mengambil data dari cache Redis `business:{$id}:active_plan_features` (TTL 3600 detik).
3. **Multi-Feature Support (OR Logic):** Mendukung parameter fitur ganda yang dipisahkan koma (`plan.feature:product_catalog,service_catalog`). Akses diizinkan jika bisnis memiliki salah satu fitur tersebut.

### B. Fast-Path User RBAC (`UserPermissionCacheService`)
1. **Runtime In-Memory Cache:** `$runtimePermissions` menyimpan hak akses user pada siklus request berjalan.
2. **Redis Scoped Cache:** `auth:user:{$userId}:business:{$businessId}:permissions` (TTL 3600 detik).
3. **Strict Explicit Permissions:** Runtime service mendukung `*` khusus bagi `is_root_user`. Di luar root user, **DILARANG KERAS menggunakan wildcard (`.*`)**; seluruh hak akses harus berupa case eksplisit di `PermissionEnum`.
4. **AppServiceProvider Gate Hook:** Seluruh panggilan `$user->can(...)` otomatis dicegat di `Gate::before` dan diarahkan ke `UserPermissionCacheService`.

### C. Invalidation Pipeline (`UserCacheObserver`)
- Perubahan `Subscription`, `SubscriptionPlan`, `Feature`, atau `BusinessType` memanggil `Business::clearCache($businessId)` dan menghapus cache `SummaryUser`.
- Mutasi `User`: Hanya perubahan struktural (`business_id`, `is_root_user`, `deleted_at`) yang menghapus permission cache. Mutasi profil biasa (`photo`, `pin`, `last_login_at`) HANYA menghapus `SummaryUser`.

---

## 5. Implementasi Kode Baku (Standard Patterns)

### A. Backend Route & FormRequest
```php
// routes/app/inventories.php
Route::middleware('plan.feature:' . FeatureEnum::INVENTORY_MANAGEMENT->value)
    ->group(function () {
        Route::post('stocks/adjustments', [StockAdjustmentController::class, 'store']);
    });

// app/Http/Requests/App/Inventory/Adjustment/StoreStockAdjustmentRequest.php
class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi RBAC WAJIB di FormRequest::authorize()
        return $this->user()?->can(PermissionEnum::INVENTORY_ADJUST_CREATE->value) ?? false;
    }
}
```

### B. Frontend Vue Component
```vue
<template>
    <!-- 1. Feature Plan Gating: Kunci dengan overlay jika paket belum mencakup fitur -->
    <div v-feature.lock="$enums.FeatureEnum.STOCK_ADJUSTMENTS" class="card">
        <h3>Penyesuaian Stok</h3>
        
        <!-- 2. User RBAC: Sembunyikan tombol jika kasir/staf tidak punya wewenang -->
        <button v-can="$enums.PermissionEnum.INVENTORY_ADJUST_CREATE" @click="openModal">
            Buat Penyesuaian
        </button>
    </div>
</template>
```

---

## 6. Integrasi dengan Client POS (Offline-First)

1. **Sinkronisasi Hak Akses Kasir:**
   - Server mengekspor data staf terdaftar pada outlet via endpoint POS `/sync/master` atau `/employees`.
   - Menggunakan Redis cache bersama `pos:outlet:{$outletId}:employees` (lengkap dengan array `permissions` ter-cache).
2. **Otorisasi Lokal di POS Client:**
   - Kasir menginput PIN 6-digit $\rightarrow$ divalidasi terhadap hash PIN lokal di Drift SQLite / Secure Storage.
   - Hak akses `is_root_user` dan `permissions` diperiksa di memori klien sebelum mengizinkan aksi sensitif (void, diskon khusus, buka cash drawer).
3. **Penyekatan Outlet Nonaktif:**
   - Jika outlet dinonaktifkan di backoffice (`is_active = false`), request sync POS langsung ditolak dengan HTTP 403 `error_code: OUTLET_INACTIVE`.
4. **Otorisasi Supervisor Kasir & Double Authorization:**
   - Fitur `FeatureEnum::DOUBLE_AUTHORIZATION` mengaktifkan flow verifikasi ganda saat kasir melakukan tindakan berisiko (misal override harga/diskon atau void transaksi).
   - Dialog otorisasi kasir memvalidasi PIN supervisor yang memiliki izin `PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION` (`transaction.validation_supervision`).
