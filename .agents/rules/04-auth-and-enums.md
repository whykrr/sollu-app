---
trigger: always_on
---

# Rule 04: Single Source of Truth Enums & Dual-Layer Otorisasi

## 1. PHP Enums sebagai Single Source of Truth (No Magic Strings)

**1. Prinsip Utama**
Backend (`app/Enums/*.php`) adalah master kebenaran status dan tipe sistem. **DILARANG KERAS** menggunakan string literal atau *magic string* di Frontend (Vue) untuk memvalidasi status/tipe (contoh anti-pattern: `v-if="status === 'draft'"`).

**2. Distribusi ke Frontend (Inertia Shared Props)**
Enum didistribusikan secara otomatis melalui Inertia Shared Props (`$enums`). Daftarkan setiap enum baru ke array `$frontendEnums` pada `app/Support/Enums/FrontendEnumProvider.php`.

**3. Standar Penggunaan di Frontend**
- **Template Vue:** Gunakan `$enums.NamaEnum.Kasus`.
  - *Contoh:* `v-if="item.status === $enums.AdjustmentStatus.Draft"`
- **Script Setup:** Gunakan composable resmi:
  - `const { enums, getOptions, getLabel, getColor } = useEnum()`
- **Form / Dropdown Options:** Gunakan `getOptions('NamaEnum')` untuk properti `:options` komponen `DropdownField` atau `SelectionGroupField`. **Dilarang keras me-hardcode array opsi di Vue!**

**4. Dynamic Entities (Bukan Enum)**
Tipe Bisnis (`BusinessType`) bersifat **100% dinamis di database** (`business_types` table). **DILARANG KERAS** mencari atau membuat `BusinessTypeEnum`. Ambil opsi dropdown/grup via `BusinessType::getAllCached()`, `BusinessType::options()`, atau `BusinessType::grouped()`.

**5. Kebijakan Zero-Orphan Permission & Sinkronisasi Role Template**
Setiap kali ada penambahan kasus baru pada `App\Enums\PermissionEnum`:
- **WAJIB** menetapkan method `label()`, `group()`, dan `groupLabel()`.
- **WAJIB** memetakan kasus permission baru tersebut ke dalam template peran POS yang relevan pada `App\Enums\RoleTemplateEnum` (misal: permission operasional F&B dipetakan ke `CASHIER_FNB`, `MANAGER_FNB`, dsb.).
- **WAJIB** menjalankan test otomatis `tests/Unit/Enums/RoleTemplateIntegrityTest.php` untuk memastikan seluruh relasi hak akses dan template peran tetap sinkron dan valid.

---

## 2. Validasi Feature Plan & Dual-Layer Authorization (SaaS Entitlement)

Aplikasi menerapkan pemisahan otorisasi dua lapis (*Dual-Layer Authorization*) yang tegas antara **Jabatan User (RBAC)** dan **Paket Berlangganan Tenant (SaaS Feature Gating)**:

```
┌────────────────────────────────────────────────────────────────────────┐
│                      Dual-Layer Authorization                          │
├─────────────────────┬───────────────────┬──────────────────────────────┤
│ Dimensi             │ User RBAC         │ Tenant SaaS Feature Plan     │
├─────────────────────┼───────────────────┼──────────────────────────────┤
│ Entitas             │ User / Employee   │ Business / Tenant            │
│ Source of Truth     │ PermissionEnum    │ FeatureEnum                  │
│ Logika Tampilan     │ Sembunyikan (Hide)│ Kunci & Upsell (FeatureLock) │
│ Directive Frontend  │ v-can             │ v-feature                    │
│ Middleware Backend  │ permission:...    │ plan.feature:...             │
└─────────────────────┴───────────────────┴──────────────────────────────┘
```

### 1. Prinsip Pemisahan Lapisan
- **User RBAC (Hak Akses Jabatan):**
  - Mengatur kewenangan aksi per pengguna (`PermissionEnum`, `v-can`).
  - Jika user tidak memiliki akses: **Sembunyikan elemen visual**.
  - Operasional umum seperti ekspor/impor data diatur murni via RBAC (`permission: product.export`), **DILARANG** di-gate oleh `plan.feature`.
- **Tenant Feature (Fitur Paket Berlangganan):**
  - Mengatur kapabilitas bisnis sesuai paket SaaS yang aktif (`FeatureEnum`, `v-feature`).
  - Jika paket bisnis belum mencakup fitur ini: **Tampilkan elemen terkunci (Upsell)** agar merchant termotivasi untuk upgrade paket.
  - **DILARANG KERAS** menggunakan pengecekan permission (RBAC) untuk memvalidasi fitur paket.

### 2. Standar Frontend UI
- **Directive:** Gunakan `v-feature="$enums.FeatureEnum.NAME"` atau `v-feature.all=[...]`.
- **Tampilan Terkunci:** **WAJIB** membungkus komponen dengan `<FeatureLock :feature="...">` atau menggunakan `<FeatureLockOverlay>`. Hindari manipulasi layout manual yang memicu layout thrashing.
- **Script Setup:** Gunakan composable: `const { hasFeature, requireFeature } = usePlanFeature()` bersama `useEnum()`.

### 3. Standar Backend
- Definisikan key konstan pada `app/Enums/FeatureEnum.php`.
- Simpan metadata fitur (nama, deskripsi, modul, grup) pada tabel database `features` (`FeatureSeeder.php`), dan petakan fitur ke paket melalui tabel pivot `plan_features` (`SubscriptionPlanSeeder.php` atau Cockpit UI). **Dilarang me-hardcode relasi fitur di PlanEnum.php**.
- Proteksi route backend menggunakan middleware: `middleware('plan.feature:' . FeatureEnum::NAME->value)`.
- Dukungan *Custom Plan* (penugasan khusus `business_id`, `is_public: false`) berjalan secara native: resolusi fitur membaca langsung relasi `plan->systemFeatures` tanpa perlu mendaftarkan kode paket baru ke PHP Enum.
