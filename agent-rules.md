# AI Development Rules

## General Rules

### Before Coding

- Selalu baca file yang relevan secara **utuh** (bukan hanya potongan) sebelum melakukan perubahan.
- Cek dependency/trait/service yang dipakai file tersebut.
- Cek migration terbaru untuk struktur tabel yang terkait dengan task.
- Cek apakah sudah ada helper/service/component serupa sebelum membuat yang baru.
- Jangan mengubah kode yang tidak berhubungan dengan task.
- Jangan melakukan refactor besar tanpa instruksi eksplisit.
- Jangan menghapus fitur yang sudah ada kecuali diminta.
- Jika menemukan ambiguity pada requirement, **tanyakan terlebih dahulu** — dilarang mengisi asumsi sendiri lalu langsung melanjutkan implementasi besar.
- Utamakan mengikuti pola yang sudah ada pada project.
- Hindari membuat dependency baru kecuali benar-benar diperlukan.
- Hindari duplicate code.

### Verification Rules

- Sebelum memanggil method/fungsi/trait yang sudah ada, **wajib baca definisinya terlebih dahulu**. Jangan asumsikan signature hanya dari nama.
- Sebelum menggunakan kolom database, **wajib cek migration atau model terkait**. Jangan asumsi nama kolom (mis. `user_id` vs `created_by`).
- Sebelum memanggil route, **wajib cek `routes/web.php` atau `routes/api.php`**. Jangan mengarang nama route.
- Sebelum menggunakan package/library, **wajib cek `composer.json` atau `package.json`** apakah sudah terinstall dan versi berapa. Jangan asumsi package tersedia.
- Jika tidak yakin suatu file/fungsi/kolom ada, AI wajib menyatakan "perlu verifikasi" dan membaca file terkait — bukan menebak lalu melanjutkan.
- Dilarang membuat contoh response API, nama variabel env, atau nilai config yang belum dikonfirmasi ada di project (mis. `.env.example`, `config/*.php`).

### Referencing Existing Code

- Saat menyebut "sudah ada pola X di project", AI **wajib menyertakan path file dan nama fungsi/class** yang menjadi rujukan.
- Dilarang berkata "biasanya di Laravel..." atau "umumnya di Vue..." tanpa mengonfirmasi apakah project ini benar-benar mengikuti pola tersebut.
- Jika AI tidak menemukan pola yang relevan di project, **wajib nyatakan itu secara eksplisit** daripada mengarang pola yang "terdengar masuk akal".

### Version Awareness

- Konfirmasi versi Laravel dan Vue yang dipakai project (cek `composer.json` / `package.json`) sebelum menggunakan syntax atau fitur tertentu.
- Dilarang menggunakan fitur dari versi framework yang lebih baru dari yang terpasang di project.

### Security

- Selalu validasi seluruh input user.
- Jangan mempercayai data dari client.
- Selalu gunakan authorization check.
- Hindari raw SQL jika ORM tersedia.
- Jangan hardcode secret, token, API key, atau password.
- Gunakan environment variable untuk konfigurasi sensitif.

---

# Backend Rules (Laravel)

## Architecture & Controllers

Ikuti struktur:

```text
Controller
    ↓
Action / Service
    ↓
Repository (optional)
    ↓
Model
```

**Pola Controller:**
Gunakan pola **hybrid**:

1. **Resource-style** (inline): Gunakan ini untuk operasi CRUD yang sederhana (tanpa side-effects yang rumit). Logika boleh ditulis langsung di dalam controller.
2. **Service-injected**: Untuk logika bisnis yang kompleks, controller harus tipis (_thin controller_). Controller hanya menangani Request validation, Authorization, dan Response. Injeksi class Service melalui _constructor_ (misal: `CreateOutletService`).

Jangan melakukan authorization hardcode:

```php
// Salah
if ($user->role == 'admin')
```

Gunakan Gate, Policy, Permission (Spatie), atau di dalam Form Request.

---

## Database

### Model

- **Urutan Trait:** Gunakan baris `use` terpisah untuk setiap trait. Urutkan dari: Third-party trait -> Framework trait -> App-specific trait.
- **UUID:** Gunakan trait `HasUuids` untuk semua Primary Key.
- Wajib menggunakan `casts()` method (Laravel 11 style) yang mereturn array.
- Pastikan menyertakan relasi dengan PHPDoc (contoh: `/** @property-read Collection|Outlet[] $outlets */`).

### Trait Patterns

- Traits di `app/Trait` (seperti `HasBusiness`, `HasOutlet`, `SortableModel`) difokuskan untuk menambahkan **Eloquent scopes**.
- Format deklarasi method di trait harus: `public function scopeNamaScope(Builder $query)`
- Jangan menaruh deklarasi relationship di dalam Traits ini.
- > **Verifikasi:** sebelum menggunakan trait di atas pada model baru, cek dulu apakah trait tersebut benar-benar ada di path tersebut dan cek method apa saja yang disediakannya - jangan asumsi.

### Query

- Gunakan model scope (`scopeFilters`) menggunakan `->when()` untuk merapikan kondisi query.
- Gunakan Eloquent terlebih dahulu, hindari `DB::select(...)` kecuali memang diperlukan.
- Hindari N+1 query. Selalu pertimbangkan eager loading (`->with()`).

---

## Migration

Rules:

- Semua foreign key wajib menggunakan constraint.
- Gunakan UUID, atau Auto Increment untuk primary key.
- Selalu tambahkan index pada: foreign key, code, sku, slug, dan kolom pencarian.
- > **Verifikasi:** sebelum menambahkan kolom baru pada migration, cek migration existing untuk tabel yang sama agar tidak terjadi duplikasi kolom atau konflik nama.

---

## Service Layer

Untuk operasi yang kompleks, logika bisnis wajib berada di Service.

- Penamaan berorientasi aksi: `CreateOrderService`, `UpdateOutletService`.
- Method utama adalah `execute(array $data, User $user)` (atau parameter serupa).
- Selalu bungkus aksi di dalam `DB::transaction()` untuk menjamin integritas.

---

## Form Request (Validation)

- **Authorization:** `authorize()` harus mengembalikan pemanggilan permission string, misal: `return Auth::user()?->can('outlet.create');`
- **Aturan Sejajar (Column-Aligned):** Format penulisan rules dalam array `=>` usahakan untuk disejajarkan (aligned) agar rapi terbaca.
- Semua validasi form harus melalui Form Request, bukan memanggil `$request->validate()` di dalam controller.

---

## API & Route

### Route Patterns

- Pendaftaran route harus rapi, dibungkus (grouped) menggunakan `prefix()`, `name()`, dan `group()`.
- Nama route wajib mengikuti standar _dot-notation_ (`entity.action`, misal: `settings.outlets.index`).
- Standar penamaan endpoint (termasuk untuk Soft Deletes):
    - `DELETE /{model}` dipetakan ke `delete` (untuk _soft delete_)
    - `PUT /{model}/restore` dipetakan ke `restore` (menggunakan `->withTrashed()`)
    - `DELETE /{model}/destroy` dipetakan ke `destroy` (untuk _force delete_)

### Response Format

Gunakan format konsisten:

```json
{
    "success": true,
    "message": "Product created",
    "data": {}
}
```

---

## Logging & Seeder

- **Logging:** Log hanya untuk Error, Integration failure, Payment failure, dan Critical event. Jangan spam log.
- **Seeder:** Seeder harus idempotent. Gunakan `updateOrCreate()` atau `firstOrCreate()`. Hindari `create()` untuk master data.

---

# Frontend Rules (Vue 3 / Inertia)

## Component Structure

Urutan komposisi komponen:

```vue
<template></template>
<script setup></script>
```

Gunakan **Composition API** (`<script setup>`).

### Component Responsibility

- Satu component satu tanggung jawab. Hindari component > 500 line.
- Pisahkan menjadi `ProductForm.vue`, `ProductFilter.vue`, dll dibungkus pada folder `Components/` per page.
- Pertimbangkan selalu penggunaan component global yang sudah ada di `resources/js/Components`.
- > **Verifikasi:** sebelum membuat component baru, cek dulu isi folder `resources/js/Components` untuk memastikan component serupa belum ada.

---

## UI Consistency & Page Patterns

### Standard List/Index Pattern

Semua list view (halaman index) **wajib** menggunakan `<Container>` (dari `resources/js/Components/UI/Container.vue`) dengan struktur:

- `<template #header>`: Berisi komponen `<Filter>` dan tombol penambahan (Action buttons).
- **Slot Default**: Berisi komponen `<Table>` lengkap dengan prop bawaan seperti `headers`, `data`, `sort`, `sort-direction`.
- `<template #footer>`: Berisi komponen `<Pagination>`.

### Form Fields & Validation Display Pattern

- Input wajib menggunakan komponen kustom yang sudah disediakan (dengan akhiran `*Field.vue`), contoh: `TextField`, `EmailField`, `NumberField`, `RadioButtonField`, dll.
- Inisialisasi state wajib menggunakan `useForm` dari `@inertiajs/vue3`.
- Semua field wajib menerapkan gaya ini untuk deteksi error validasi:

```html
<TextField
    id="name"
    v-model="form.name"
    label="Nama Lengkap"
    :class="{ 'is-invalid': form.errors.name }"
    :feedback="form.errors.name"
/>
```

### PopUpPage vs Modal Pattern

- Gunakan `PopUpPage.vue` (Side Panel) untuk menampilkan form edit/create, atau menampilkan detail entitas berukuran besar.
- Gunakan `Modal.vue` / `ModalDelete.vue` (Centered) hanya untuk peringatan konfirmasi atau aksi singkat.
- **Detail Ber-Tab (Tabbed Detail):** Untuk halaman detail pengaturan yang kompleks (contoh: Settings Outlet), wajib menjadikan `PopUpPage` sebagai wrapper dari komponen `Tab`, lalu memisahkan isinya ke komponen-komponen terpisah di dalam folder `Tabs/` (seperti `GeneralTab.vue`, `DevicesTab.vue`).

### Data Fetching & Inertia Routing

- Pengiriman data Form (`form.post()`, `form.put()`) selalu menyertakan `preserveState: true` dan `preserveScroll: true`.
- Transisi atau memuat detail dasar menggunakan metode `router.visit()` dengan **partial reloads** (`only: ['nama_prop']`).
- **PENGAMBILAN DATA KOMPLEKS:** Untuk pemuatan data yang besar / kompleks (khususnya untuk data dalam tab sub-halaman), gunakan API (seperti Axios) agar halaman tidak freeze. Route dari API ini harus tetap terdaftar dan mematuhi standardisasi pada `routes/web.php` (bukan di `api.php`). Gunakan komponen _Deferred_ dengan kelas `.placeholder` pada transisinya.

### Filter Pattern

- State filter dideklarasikan menggunakan `reactive()` (bukan `useForm`).
- Terdapat `watch` dengan `debounce` (misal: 500ms) untuk auto-submit.
- Saat state filter berubah, request pengajuan wajib mengatur ulang paginasi ke halaman pertama (`page: 1`).

---

## CSS & Tailwind Rules

### Hybrid Tailwind / Custom Class

- Anda wajib mengedepankan pola _hybrid_. Gunakan custom class bawaan project (seperti `btn`, `btn-main`, `btn-success`, `form`, `badge`, `badge-success`) untuk elemen-elemen UI standar aplikasi.
- Gunakan Tailwind Utilities MURNI untuk layout dan pengaturan margin/padding (seperti `flex flex-col gap-3 items-center justify-between`).
- Jika utility class Tailwind mulai terlihat **terlalu panjang**, ekstrak utilitas tersebut menjadi class custom pada `resources/css/app.css` (misal: `.card-header`).
- Skala spacing (gap): Gunakan gap skala 4 (`gap-4 space-x-3`) untuk elemen wajar. Skala 3 untuk padding main area. Skala 2 untuk gap antar komponen bungkus.

---

# What AI Must Never Do

- Menghapus migration lama.
- Mengubah database schema tanpa instruksi.
- Mengubah permission yang sudah ada tanpa instruksi.
- Menghapus audit trail atau soft delete.
- Mengubah UUID menjadi auto increment.
- Membuat breaking changes tanpa penjelasan.
- Menggunakan package tambahan tanpa persetujuan.
- **Mengklaim suatu fungsi/kolom/route ada tanpa mem verifikasi nya di kode.**
- **Menulis kode yang memanggil API/service eksternal dengan asumsi format response tanpa mengecek dokumentasi atau kode yang sudah ada.**
- **Menyatakan task "selesai dan teruji" tanpa benar-benar menjalankan atau menunjukkan bagaimana itu diverifikasi.**
- **Membuat asumsi silent terhadap requirement yang ambigu — harus ditanyakan terlebih dahulu.**

---

# Definition of Done

Sebelum menyatakan task selesai, AI wajib memastikan:

- build vue menggunakan `npm run build` pastikan tidak ada error.
- Semua fungsi/method yang dipanggil benar-benar ada (sudah diverifikasi, bukan diasumsikan).
- Tidak ada import yang tidak terpakai atau tidak valid.
- Migration/model/route yang di referensi kan sudah di cek keberadaannya.
- Kode sudah dibaca ulang sekali untuk memastikan konsisten dengan pola project.
- Jika ada bagian yang tidak bisa diverifikasi (mis. tidak ada akses ke DB atau environment), AI wajib menyebutkan ini secara eksplisit sebagai asumsi terbuka, bukan diam-diam melanjutkan.
- Pastikan semua behavior pada frontend berjalan dengan baik

---

# Output Requirements

Saat membuat kode, AI wajib menyampaikan:

1. **Summary** — ringkasan perubahan yang dilakukan.
2. **Files Changed** — daftar file yang diubah/dibuat.
3. **Reasoning** — alasan di balik perubahan.
4. **Verification** — apa saja yang sudah di cek/diverifikasi sebelum menulis kode (mis. "sudah cek migration `xxx`, kolom `yyy` memang ada").
5. **Potential Impact** — dampak potensial dari perubahan.
6. **Testing Steps** — contoh langkah testing jika diperlukan.
7. **Open Assumptions** — asumsi yang belum terverifikasi (jika ada), agar user bisa mengonfirmasi.

Format:

```text
Summary
Files Changed
Reasoning
Verification
Potential Impact
Testing Steps
Open Assumptions
```

## Confidence Level

- **HIGH:** file telah dibaca, dependency telah diverifikasi.
- **MEDIUM:** sebagian dependency belum diverifikasi.
- **LOW:** terdapat asumsi signifikan.
