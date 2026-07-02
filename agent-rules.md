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

### Code Quality

- Tulis kode yang mudah dibaca dan dipelihara.
- Gunakan nama variabel yang jelas dan konsisten.
- Jangan menggunakan magic number atau magic string.
- Buat fungsi kecil dengan satu tanggung jawab.
- Hindari nested condition yang terlalu dalam.
- Hindari premature optimization.

### Security

- Selalu validasi seluruh input user.
- Jangan mempercayai data dari client.
- Selalu gunakan authorization check.
- Hindari raw SQL jika ORM tersedia.
- Jangan hardcode secret, token, API key, atau password.
- Gunakan environment variable untuk konfigurasi sensitif.

---

# Backend Rules (Laravel)

## Architecture

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

Controller hanya bertugas:

- Request validation
- Authorization
- Memanggil service
- Return response

Controller tidak boleh berisi:

- Business logic
- Query kompleks
- Perhitungan bisnis

---

## Validation

Gunakan Form Request.

Benar:

```php
StoreProductRequest
```

Salah:

```php
$request->validate([...]);
```

## Authorization

Gunakan:

```php
Gate
Policy
Permission
Form Request
```

Jangan melakukan:

```php
if ($user->role == 'admin')
```

langsung di controller.

---

## Database

### Model

Gunakan trait untuk mengisolasi data per bisnis atau per outlet:

- `app/Trait/HasBusiness.php`
- `app/Trait/HasOutlet.php`

Gunakan trait `app/Trait/SortableModel.php` untuk model yang datanya dapat disorting pada frontend.

Implementasikan trait untuk method yang sering dipakai di beberapa model.

> **Verifikasi:** sebelum menggunakan trait di atas pada model baru, cek dulu apakah trait tersebut benar-benar ada di path tersebut dan cek method apa saja yang disediakannya - jangan asumsi.

### Query

Gunakan Eloquent terlebih dahulu.

Hindari:

```php
DB::select(...)
```

kecuali memang diperlukan.

### N+1

Selalu pertimbangkan eager loading.

Benar:

```php
Product::with('category')->get();
```

---

## Migration

Rules:

- Semua foreign key wajib menggunakan constraint.
- Gunakan UUID, atau Auto Increment untuk primary key.
- Selalu tambahkan index pada:
    - foreign key
    - code
    - sku
    - slug
    - kolom pencarian

> **Verifikasi:** sebelum menambahkan kolom baru pada migration, cek migration existing untuk tabel yang sama agar tidak terjadi duplikasi kolom atau konflik nama.

---

## Service Layer

Business logic wajib berada di Service.

Contoh:

```php
CreateOrderService
UpdateInventoryService
CalculateTaxService
```

---

## API

### Response Format

Gunakan format konsisten:

```json
{
    "success": true,
    "message": "Product created",
    "data": {}
}
```

Error:

```json
{
    "success": false,
    "message": "Validation error",
    "errors": {}
}
```

---

## Logging

Log hanya untuk:

- Error
- Integration failure
- Payment failure
- Critical event

Jangan spam log.

---

## Seeder

Seeder harus idempotent.

Gunakan:

```php
updateOrCreate()
firstOrCreate()
```

Hindari:

```php
create()
```

untuk master data.

---

## Testing

Minimal test untuk:

- Authentication
- Authorization
- Business critical flow
- API endpoint

---

# Frontend Rules (Vue 3)

## Component Structure

Urutan:

```vue
<template></template>
<script setup></script>
```

---

## Component Responsibility

Satu component satu tanggung jawab.

Hindari component > 500 line.

Pisahkan menjadi:

```text
ProductForm.vue
ProductFilter.vue
```

dibungkus pada folder component per page.

Pertimbangkan selalu penggunaan component global yang sudah ada di `resources/js/Components`.

> **Verifikasi:** sebelum membuat component baru, cek dulu isi folder `resources/js/Components` untuk memastikan component serupa belum ada.

---

## UI Consistency

### 1. Gunakan design system yang sama

Button:

```html
btn-primary btn-secondary btn-danger
```

Jangan membuat style baru untuk kasus yang sama.

### 2. Selalu gunakan component PopUpPage untuk sub halaman

Gunakan Component `resources/js/Components/UI/PopUpPage.vue` untuk:

- Menampilkan form input fitur tersebut
- Menampilkan data detail atau data lainnya (mis. data invoice, dll)

Gunakan API untuk partial load data detail atau terkait untuk komponen pada sub halaman.

### 3. Gunakan axios untuk pengambilan partial data.

- Gunakan composable axios untuk memudahkan pemeliharaan kode
- Gunakan Deferred Component untuk data yang bermuatan berat
- Gunakan class `.placeholder` untuk deferred component

### 4. Gunakan property feedback pada form untuk handling error dari validasi request

Selalu gunakan property `feedback` saat penggunaan component di folder `resources/js/Components/Form`

```html
feedback="form.errors.name"
```

---

## Tailwind Rules

### Utamakan utility class

Jika class mulai panjang:

```html
class="flex items-center justify-between px-4 py-3 ..."
```

ekstrak menjadi:

```css
.card-header
```

pada file `resources/css/app.css`.

### Pengaturan Space

Gunakan `gap`, atau `whitespace` maximum skala 4:

```html
class="gap-4 space-x-3"
```

- skala 3 untuk padding atau margin pada main area
- skala 2 untuk gap antar component pembungkus pada main area

---

## Accessibility

Selalu tambahkan:

```html
label aria-label type autocomplete
```

untuk form.

---

## Error Handling

Seluruh API call wajib memiliki:

```js
try {
} catch (error) {
} finally {
}
```

Jangan mengabaikan error.

---

# What AI Must Never Do

AI dilarang:

- Menghapus migration lama.
- Mengubah database schema tanpa instruksi.
- Mengubah permission yang sudah ada tanpa instruksi.
- Menghapus audit trail.
- Menghapus soft delete.
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

- Semua fungsi/method yang dipanggil benar-benar ada (sudah diverifikasi, bukan diasumsikan).
- Tidak ada import yang tidak terpakai atau tidak valid.
- Migration/model/route yang di referensi kan sudah di cek keberadaannya.
- Kode sudah dibaca ulang sekali untuk memastikan konsisten dengan pola project.
- Jika ada bagian yang tidak bisa diverifikasi (mis. tidak ada akses ke DB atau environment), AI wajib menyebutkan ini secara eksplisit sebagai asumsi terbuka, bukan diam-diam melanjutkan.

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

---
