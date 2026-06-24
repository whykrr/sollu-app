# AI Development Rules

## General Rules

### Before Coding

- Selalu baca file yang relevan terlebih dahulu sebelum melakukan perubahan.
- Jangan mengubah kode yang tidak berhubungan dengan task.
- Jangan melakukan refactor besar tanpa instruksi eksplisit.
- Jangan menghapus fitur yang sudah ada kecuali diminta.
- Jika menemukan ambiguity, tanyakan terlebih dahulu.
- Utamakan mengikuti pola yang sudah ada pada project.
- Hindari membuat dependency baru kecuali benar-benar diperlukan.
- Hindari duplicate code.

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

Gunakan trait untuk mengisolasi data per bisnis atau per outlet

- app/Trait/HasBusiness.php
- app/Trait/HasOutlet.php

Gunakan trait app/Trait/SortableModel.php untuk model yang data nya dapat di sorting pada frontend.
Implementasikan trait untuk method yang sering di pakai di beberapa model

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
- Gunakan UUID untuk primary key.
- Selalu tambahkan index pada:
    - foreign key
    - code
    - sku
    - slug
    - kolom pencarian

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

Pertimbangkan selalu penggunaan component global yang sudah ada di resources/js/Components

---

## UI Consistency

### 1. Gunakan design system yang sama.

Button:

```html
btn-primary btn-secondary btn-danger
```

Jangan membuat style baru untuk kasus yang sama.

---

### 2. Selalu gunakan component PopUpPage untuk sub halaman.

component resources/js/Components/UI/PopUpPage.vue

gunakan best practice inertia dalam pengambilan data untuk component sub halaman

---

### 2. Gunakan property feedback pada form.

selalu gunakan property feedback saat penggunaan component Form

```html
feedback="form.errors.name"
```

---

## Tailwind Rules

### Utamakan utility class.

Jika class mulai panjang:

```html
class="flex items-center justify-between px-4 py-3 ..."
```

ekstrak menjadi:

```css
.card-header
```

pada file resources/css/app.css.

### Pengaturan Space

gunakan gap, atau whitespace maximum skala 4

```html
class="gap-4 space-x-3"
```

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

---

# Output Requirements

Saat membuat kode:

1. Jelaskan perubahan yang dilakukan.
2. Jelaskan alasan perubahan.
3. Sebutkan file yang diubah.
4. Sebutkan potensi impact.
5. Berikan contoh testing jika diperlukan.

Format:

```text
Summary
Files Changed
Reasoning
Potential Impact
Testing Steps
```

---
