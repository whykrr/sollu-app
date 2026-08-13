---
name: sollu-backend
description: >-
  Backend development standards for Sollu App (Laravel 11.9+, PHP 8.3).
  MUST trigger whenever creating or editing Laravel controllers, Eloquent models (casts method, UUIDs),
  domain service classes (Single vs Split services), BaseInertiaFormRequest, DB migrations, query performance (N+1 limit 5s),
  API JSON responses (JsonResource), or controller response messages (ResourceMessage/FlashDataVariable constants).
---

# Sollu Backend Rules (Laravel 11.9+)

Standard pengembangan backend pada Sollu App menggunakan Laravel 11.9+ dan PHP 8.3.

## 1. Architecture & Controllers

- **Flow:** Controller → Action/Service → Repository (opsional) → Model.
- **Controller Pattern:** Hybrid approach:
  - *Resource-style (inline):* CRUD sederhana dapat langsung ditulis di controller.
  - *Service-injected:* Logika bisnis kompleks wajib di-offload ke Service Class via Constructor Injection.
- **Authorization:** Gunakan `$this->authorize('permission.name')` atau `Gate::authorize()`. Dilarang menggunakan middleware di `__construct()`.

## 2. Model Standards (Laravel 11)

- **Member Ordering:**
  1. `use` Traits (satu per baris, misal: `use HasFactory, HasUuids, SoftDeletes;`)
  2. Properti: `$fillable`, `$hidden`, `$sortable`, `$appends`
  3. Method `casts(): array` (Style Laravel 11 dengan panah `=>` rapi)
  4. Method Notifikasi Custom
  5. Relationships (Urutan: `BelongsTo` → `HasMany` → `BelongsToMany` → `HasOne`; return type explicit `: BelongsTo`)
  6. `scopeFilters()` & Scopes lainnya
  7. Custom Helpers / Methods
- **PHPDoc:** Selalu tambahkan `@property-read Collection|Outlet[] $outlets` untuk membantu Autocomplete IDE / Larstan.

## 3. Form Requests (`BaseInertiaFormRequest`)

- **Base Class:** Semua Form Request wajib menginduk ke `App\Http\Requests\BaseInertiaFormRequest`.
- **Naming:** `Get{Entity}Request`, `Store{Entity}Request`, `Update{Entity}Request`.
- **Authorization:** Kembalikan cek permission pada method `authorize()`.
- **Validation Rules:** Format rules dalam bentuk array dengan panah `=>` sejajar:
  ```php
  public function rules(): array
  {
      return [
          'name' => ['required', 'string', 'max:255'],
          'sku'  => ['nullable', 'string', 'max:100'],
      ];
  }
  ```

## 4. Service Layer Standards

- **Single-File Service (≤500 baris, kompleksitas rendah):** Gabungan domain service (contoh: `app/Services/OutletService.php`) memuat method `create()`, `update()`, `delete()`.
- **Split-File Service (>500 baris atau kompleks):** Single-action class per file (contoh: `app/Services/Outlet/CreateOutletService.php`) dengan method utama `execute(array $data, User $user)`.
- **Database Transactions:** Bungkus setiap mutasi multi-tabel dalam `DB::transaction(function () { ... });`.
- **Audit Log:** Catat perubahaan data penting menggunakan `AuditLogService`.

## 5. Query Optimization & Performance Limits (Max 5s)

- **Waktu Eksekusi Query/Response:** Dilarang melebihi **5 detik**.
- **N+1 Query Prevention:** Selalu gunakan Eager Loading (`with()`) untuk query standar Eloquent.
- **DataTables & Pagination:**
  - Jangan load relasi berat pada `index()`; gunakan `withCount()` untuk jumlah data relasi.
  - Jika memerlukan *sorting* atau *filtering* pada kolom tabel relasi, gunakan `join()` atau `leftJoin()` di tingkat database untuk efisiensi memori.
- **Offload Complex Detail & Secondary Data:** Sediakan endpoint API JSON (`JsonResource`) tersendiri untuk data detail kompleks (diakses via PopUpPage) atau data sekunder (opsi dropdown dinamis), dilarang di-load di Inertia `index()`.
- **Large Datasets:** Gunakan `chunk()`, `lazy()`, atau `cursor()` untuk pengolahan data dalam jumlah besar.

## 6. API JSON Response Standards

- **Key Format:** `snake_case`.
- **Status Codes:** Mengacu pada standar HTTP (200, 201, 400, 404, 422, 500). Tidak menggunakan wrapper custom `"success": true`.
- **Data & Meta:** Gunakan `JsonResource`. Bungkus koleksi data dalam `"data"` dan data paginasi dalam `"meta"`.
- **Numeric & Decimal Casting (`(float)` / `(double)`):** Seluruh nilai desimal dan numerik (harga, stok, persentase, bobot) pada `JsonResource` atau respon API WAJIB di-cast ke tipe angka murni `(float)` atau `(double)`. Dilarang mengirimkan string berformat desimal (contoh salah: `"10.50"`), wajib dikirim sebagai angka murni (contoh benar: `10.5`).
  ```php
  public function toArray(Request $request): array
  {
      return [
          'id'            => $this->id,
          'name'          => $this->name,
          'base_price'    => (float) $this->amount,
          'current_stock' => (float) $this->current_stock,
      ];
  }
  ```
- **Validation Error (422):** Format default FormRequest (`"message"`, `"errors"`).

## 7. Controller Response Messages & Constants (MANDATORY)

- **DILARANG MENGGUNAKAN HARDCODED STRING:** Dilarang keras menuliskan string pesan respon manual langsung di Controller (contoh salah: `->with('success', 'Data berhasil dibuat')`).
- **WAJIB MENGGUNAKAN CONSTANT / TRANSLATION:** Seluruh *flash message* respon Controller wajib merujuk pada Class Constant di `app/Constants/` atau Translation helper `__('key')` / `trans('key')`.

### Referensi Constant Resmi Proyek:
- `App\Constants\ResourceMessage::CREATE_SUCCESS` (`'Data berhasil dibuat!'`)
- `App\Constants\ResourceMessage::UPDATE_SUCCESS` (`'Data berhasil diperbarui!'`)
- `App\Constants\ResourceMessage::DELETE_SUCCESS` (`'Data dipindah ke sampah!'`)
- `App\Constants\ResourceMessage::RESTORE_SUCCESS` (`'Data berhasil di kembalikan!'`)
- `App\Constants\ResourceMessage::PURGE_SUCCESS` (`'Data berhasil di hapus!'`)
- `App\Constants\AuthorizationMessage::CANT_ACCESS_PAGE`
- `App\Constants\AuthorizationMessage::CANT_ACCESS_DATA`
- `App\Constants\FlashDataVariable::SUCCESS->value` (`'success'`)
- `App\Constants\FlashDataVariable::WARNING->value` (`'warning'`)
- `App\Constants\FlashDataVariable::FAILED->value` (`'failed'`)

### Contoh Penggunaan di Controller:
```php
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;

public function store(StoreOutletRequest $request)
{
    $this->outletService->create($request->validated());

    return redirect()->back()->with(
        FlashDataVariable::SUCCESS->value,
        ResourceMessage::CREATE_SUCCESS
    );
}
```
