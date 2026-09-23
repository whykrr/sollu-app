# Rule 03: PHP Enums SSOT & Dual-Layer Otorisasi

## 1. PHP Enums sebagai Single Source of Truth (No Magic Strings)
- **Backend adalah Master:** Seluruh status, tipe, dan kategori didefinisikan di `app/Enums/*.php`. Dilarang menggunakan *magic strings* di Vue (anti-pattern: `v-if="status === 'draft'"`).
- **Distribusi ke Frontend:** Otomatis melalui Inertia Shared Props (`$enums`). Daftarkan enum baru ke `FrontendEnumProvider.php`.
- **Penggunaan di Vue:**
  - Template: `$enums.AdjustmentStatus.Draft`
  - Script: `const { enums, getOptions, getLabel, getColor } = useEnum()`
  - Dropdown Options: `:options="getOptions('AdjustmentReason')"` (Dilarang me-hardcode opsi di Vue).
- **Entitas Dinamis (Bukan Enum):** `BusinessType` bersifat 100% dinamis di DB (`business_types`). Dilarang membuat `BusinessTypeEnum`. Ambil via `BusinessType::getAllCached()` / `BusinessType::options()`.
- **Zero-Orphan Permission & Role Template:**
  - Setiap case baru pada `PermissionEnum` WAJIB memiliki method `label()`, `group()`, `groupLabel()`.
  - WAJIB dipetakan ke template peran POS di `RoleTemplateEnum` dan lolos `tests/Unit/Enums/RoleTemplateIntegrityTest.php`.

---

## 2. Dual-Layer Authorization (User RBAC vs SaaS Feature Plan)

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

1. **User RBAC (Hak Akses Jabatan):**
   - Mengatur kewenangan per user (`PermissionEnum`, `v-can`).
   - Jika tidak ada hak akses: **Sembunyikan elemen visual**.
   - Operasional umum (ekspor/impor) diatur murni via RBAC (`permission: product.export`), bukan via plan feature.
2. **Tenant Feature (Fitur Paket Berlangganan):**
   - Mengatur kapabilitas bisnis sesuai paket SaaS yang aktif (`FeatureEnum`, `v-feature`).
   - Jika paket belum mencakup fitur: **Tampilkan elemen terkunci (Upsell)** dengan `<FeatureLock :feature="...">` atau `<FeatureLockOverlay>`.
   - Backend route protection: `middleware('plan.feature:' . FeatureEnum::NAME->value)`.
   - Dilarang keras mencampuradukkan pengecekan permission untuk membatasi fitur paket.
