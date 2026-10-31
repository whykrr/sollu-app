# Rule 06: Standar MCP Tooling & Penegakan Pengujian (Testing)

## 1. Standarisasi Penggunaan MCP (Model Context Protocol)
- **Git MCP:** Prioritaskan alat Git MCP (`status`, `add`, `commit`, `checkout`, `stash_save`, `stash_pop`) dibandingkan terminal raw git.
- **Conventional Commits:** `feat(scope): ...`, `fix(scope): ...`, `refactor(scope): ...`, `style: ...`, `test(scope): ...`, `chore: ...`. Commit hanya saat kode terverifikasi bersih.
- **Sollu Project Inspector MCP (`sollu-project`):** Manfaatkan `check_enum_integrity`, `trace_feature_stack`, `audit_tenant_isolation`, `inspect_inventory_state`, dan `lint_agent_rules`.

---

## 2. Struktur 5-Layer Pengujian & Anti-Test Proliferation

Setiap penambahan fitur (*build*), modifikasi logika bisnis (*enhancement*), atau perbaikan bug (*bugfix*) **WAJIB** menerapkan pembagian 5 layer pengujian pragmatis dan prinsip konsolidasi suite:

### A. Prinsip Anti-Test Proliferation (Augment over Proliferate)
- **Dilarang Membuat File Test Ad-hoc:** Jika memodifikasi, menambah fitur, atau memperbaiki bug pada kelas/endpoint/service yang sudah ada, **WAJIB menambahkan skenario uji (*test case*) ke dalam file test eksisting**.
- **Larangan Keras:** Dilarang membuat file test baru sementara per-prompt (seperti `BugfixXTest.php`, `DiscountCheckTest.php`, dsb). File test baru HANYA diizinkan jika memang membuat Class, Service, Controller, atau Bounded Context baru yang belum memiliki file test pasangan.

### B. Pembagian 5-Layer Pengujian
1. **Unit Test (`tests/Unit/`):**
   - **Fokus:** Logika komputasi murni tanpa state (*pure stateless logic*, helper string/format, kalkulasi formula matematis, DTO/Value Objects, integritas PHP Backed Enum).
   - **Aturan:** Menggunakan `PHPUnit\Framework\TestCase`. Dilarang menyentuh database fisik/SQLite (`RefreshDatabase`) dan dilarang me-mocking query/model Eloquent yang rumit. Eksekusi ultra-cepat (< 1ms).
2. **Feature Test (`tests/Feature/`):**
   - **Fokus:** 1 fitur / HTTP Endpoint tunggal dari request hingga response.
   - **Aturan:** Menguji validasi Form Request, Controller, isolasi multi-tenant (`business_id`/`outlet_id`), dual-layer auth (Spatie `v-can` & SaaS Feature Gate `v-feature`), serta response payload (Inertia props / JSON Resource). Menggunakan `RefreshDatabase` (SQLite In-Memory).
3. **Integration & Service Test (`tests/Integration/` / `tests/Feature/Services/`):**
   - **Fokus:** Service Layer, Jobs, orkestrasi mutasi multi-tabel, dan interaksi lintas bounded context.
   - **Aturan:** **WAJIB menggunakan database nyata (`RefreshDatabase`)** untuk memverifikasi kebenaran mutasi data, integritas foreign key/unique constraint, dan atomisitas `DB::transaction`. **DILARANG me-mocking query Eloquent/Model.** Gunakan **Laravel Fakes resmi** (`Event::fake()`, `Queue::fake()`, `Notification::fake()`, `Storage::fake()`) untuk side-effects eksternal.
4. **Browser / E2E Test (`tests/Browser/`):**
   - **Fokus:** Alur user sebenarnya (*critical user journey*) dengan browser engine nyata via Laravel Dusk.
   - **Aturan:** Menguji interaksi DOM antarmuka Vue 3 / Inertia (Login $\rightarrow$ POS $\rightarrow$ Drawer `<PopUpPage>` $\rightarrow$ Submit footer $\rightarrow$ Toast notifikasi).
5. **Regression Test (`tests/Regression/` atau Augmentasi Test Domain Terkait):**
   - **Fokus:** Reproduksi bug spesifik dari production atau issue tracker untuk mencegah *bug recurrence*.
   - **Aturan:** Masukkan skenario reproduksi bug ke dalam file Feature/Service Test domain yang bersangkutan (atau `tests/Regression/` jika mencakup multi-modul yang kompleks) sebelum perbaikan kode dilakukan (TDD Bugfix).

---

## 3. Protokol Alur Kerja TDD & Test Blast Radius

Saat mengembangkan aplikasi dengan pendekatan TDD (Test-Driven Development):

### A. Protokol Dual-Loop (Inner vs Outer Loop)
1. **Inner Loop (Micro-Cycle: Red $\rightarrow$ Green):**
   - Tambahkan skenario kegagalan (*failing test*) pada test suite yang relevan.
   - Agen **diizinkan** menjalankan targeted test tunggal untuk kecepatan umpan balik instan (< 2 detik):
     `php artisan test --filter=test_nama_metode` atau `php artisan test tests/Feature/.../SpecificTest.php`
   - Tulis kode implementasi minimal hingga targeted test tersebut berubah hijau (*Green*).
2. **Outer Loop (Refactor & Verification - WAJIB Sebelum Selesai):**
   - **DILARANG KERAS** mengklaim tugas selesai hanya berdasarkan 1 targeted test yang baru dijalankan!
   - Saat tahap *Refactor* dan sebelum menyelesaikan tugas, agen **WAJIB mengeksekusi Test Blast Radius** (seluruh test suite terkait).
   - Pastikan seluruh legacy tests tetap lulus 100% tanpa regresi. Jika ada legacy test yang gagal, perbaiki implementasi kode agar kompatibel dengan kontrak yang ada.

### B. Pemetaan Test Blast Radius (Lingkup Uji Wajib)
Saat mengubah komponen tertentu, berikut cakupan pengujian minimal yang WAJIB dijalankan:
- **Modifikasi Controller / FormRequest / Route:**
  $\rightarrow$ Jalankan seluruh `tests/Feature/<Domain>/` terkait.
- **Modifikasi Service Layer / Business Logic:**
  $\rightarrow$ Jalankan test service tersebut + seluruh test di `tests/Feature/Services/<Domain>/` + Feature Controller pemanggilnya.
- **Modifikasi Skema Database / Migrasi / Model Event:**
  $\rightarrow$ Jalankan seluruh suite domain terkait + Cross-Domain Integration Tests (misal `tests/Feature/Services/Transaction/B2bCrossDomainIntegrationTest.php`).
- **Modifikasi Trait / Shared Core / Middleware:**
  $\rightarrow$ Jalankan semua Feature Test yang mengonsumsi komponen tersebut.

---

## 4. Prinsip Penegakan Kualitas Uji: Target 0% Error Production

Untuk menjamin **0% error pada logika, integrasi, dan data** saat rilis ke production:

1. **Anti-Brittle Mocking:** Jangan pernah me-mock query builder Eloquent atau struktur database di unit/service test. Mocking hanya untuk gateway pihak ketiga (misal: payment gateway external, SMS API) atau Laravel Fakes untuk side-effects.
2. **Multi-Tenant Isolation Assurance:** Setiap penambahan data wajib dites dengan multi-tenant assertion:
   - Tenant A membuat data $\rightarrow$ Tenant B tidak boleh dapat melihat (`assertDatabaseMissing` / `assertForbidden` / `404`).
3. **Database Transaction & Atomicity Guard:** Setiap alur bisnis yang melibatkan multi-tabel (`DB::transaction`) wajib diuji jalur kegagalannya (*exception path*) untuk memastikan bahwa jika step ke-2 gagal, step ke-1 di-rollback 100% (tidak ada data menggantung / corrupt).
4. **Edge Cases & Number Precision:** Uji nilai batas secara eksplisit: kuantitas 0, angka minus, desimal ekstrem pada HPP/harga, pembagian dengan nol (*division by zero*), dan stok habis.
5. **Side-Effect Assertion:** Verifikasi bahwa event, background job, dan notifikasi yang dipicu memiliki parameter/recipient yang tepat menggunakan method assertion Laravel Fakes (misal: `Notification::assertSentTo(...)`).

---

## 5. Protokol Wajib: Open Question Skenario Pengujian (Test Case Alignment)
Saat merancang implementasi fitur baru (*build*) maupun pengembangan (*enhancement*):
- **WAJIB** mengajukan pertanyaan terbuka (*open question*) atau menyajikan matriks skenario test yang direncanakan kepada user/stakeholder untuk diselaraskan sebelum mengeksekusi testing.
- Matriks skenario uji minimal mencakup:
  1. *Happy Path* (alur sukses standar & variasi input valid).
  2. *Edge Cases* (nilai batas, 0, desimal ekstrem, stok habis, concurrent/race condition).
  3. *Validation & Exception Path* (validasi form gagal, saldo tidak cukup, status transisi ilegal).
  4. *Multi-Tenant Isolation & Authorization* (pencegahan kebocoran data antar tenant, role permission check).
  5. *Cross-Domain Side Effects* (ledger mutasi stok, pencatatan audit log, dispatch notifikasi/mail).

---

## 6. Definition of Done (DoD)
Tugas dinyatakan selesai (*Done*) HANYA JIKA:
1. Linter PHP & Frontend bersih (`vendor/bin/pint --dirty` dan `npm run lint`).
2. **Seluruh automated test dalam Test Blast Radius (Unit, Feature, Integration, dan Legacy Tests terkait)** lolos 100% (`100% passing`). Tidak ada legacy test yang di-skip atau diabaikan.
3. Tidak ada penambahan file test ad-hoc baru yang memicu *test sprawl* (test baru terintegrasi rapi pada suite domain yang ada).
4. Skenario test case telah dikonfirmasi dan mencakup jalur kritis bisnis serta multi-tenant scoping.
5. Verifikasi dua arah migrasi database (`migrate -> rollback -> migrate`) berhasil tanpa error jika ada perubahan skema database (Rule 08).
6. Tidak ada error/warning console pada antarmuka frontend.

---

## 7. Sandbox Constraints & Protokol Eksekusi Bertahap

### A. Terminal Sandbox Network Isolation (PostgreSQL & Git)
1. **Koneksi Database Lokal (`BypassSandbox: true`):** Standard Sandbox Mode memblokir akses jaringan eksternal dan loopback socket. Perintah yang membutuhkan koneksi langsung ke PostgreSQL lokal (`127.0.0.1:5432`) seperti:
   - `php artisan migrate` / `migrate:rollback`
   - `php artisan test`
   - `php artisan db:seed`
   **WAJIB dieksekusi dengan `BypassSandbox: true`** untuk mencegah error `SQLSTATE[08006] Connection refused`.
2. **Operasi Git Berbasis Lock:** Perintah Git yang membuat lock file di dalam direktori repository (seperti `git checkout`, `git commit`, `git stash`) membutuhkan `BypassSandbox: true` karena batasan write file system sandbox pada `.git/index.lock`.

### B. Protokol Eksekusi Bertahap Per Fase (Strict Phased Execution)
1. **Disiplin Fase Tunggal:** Jika perencanaan kerja dipecah menjadi beberapa fase (Fase 1, Fase 2, dst.), agen **WAJIB HANYA fokus pada fase yang sedang aktif**.
2. **Larangan Mendahului:** Dilarang keras melakukan implementasi atau modifikasi kode pada fase berikutnya sebelum fase aktif selesai, diverifikasi dengan tes/linter, dilaporkan ke pengguna, dan disetujui untuk lanjut.
