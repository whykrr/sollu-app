# Rule 06: Standar MCP Tooling & Penegakan Pengujian (Testing)

## 1. Standarisasi Penggunaan MCP (Model Context Protocol)
- **Git MCP:** Prioritaskan alat Git MCP (`status`, `add`, `commit`, `checkout`, `stash_save`, `stash_pop`) dibandingkan terminal raw git.
- **Conventional Commits:** `feat(scope): ...`, `fix(scope): ...`, `refactor(scope): ...`, `style: ...`, `test(scope): ...`, `chore: ...`. Commit hanya saat kode terverifikasi bersih.
- **PostgreSQL Core MCP (`sollu-db`):** **HANYA QUERY READ-ONLY (`SELECT`).** DILARANG KERAS mutasi skema (`ALTER`, `DROP`) atau data (`INSERT`, `UPDATE`, `DELETE`) langsung via MCP.
- **Sollu Project Inspector MCP (`sollu-project`):** Manfaatkan `check_enum_integrity`, `trace_feature_stack`, `audit_tenant_isolation`, `inspect_inventory_state`, dan `lint_agent_rules`.

---

## 2. Struktur 5-Layer Pengujian (Testing Strategy)
Setiap penambahan fitur (*build*), modifikasi logika bisnis (*enhancement*), atau perbaikan bug (*bugfix*) **WAJIB** menerapkan pembagian 5 layer pengujian pragmatis:

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
5. **Regression Test (`tests/Regression/`):**
   - **Fokus:** Reproduksi bug spesifik dari production atau issue tracker untuk mencegah *bug recurrence*.
   - **Aturan:** Setiap ada bugfix, wajib dibuatkan test reproduksi terlebih dahulu (TDD bugfix) sebelum kode diperbaiki.

---

## 3. Prinsip Penegakan Kualitas Uji: Target 0% Error Production

Untuk menjamin **0% error pada logika, integrasi, dan data** saat rilis ke production:

1. **Anti-Brittle Mocking:** Jangan pernah me-mock query builder Eloquent atau struktur database di unit/service test. Mocking hanya untuk gateway pihak ketiga (misal: payment gateway external, SMS API) atau Laravel Fakes untuk side-effects.
2. **Multi-Tenant Isolation Assurance:** Setiap penambahan data wajib dites dengan multi-tenant assertion:
   - Tenant A membuat data $\rightarrow$ Tenant B tidak boleh dapat melihat (`assertDatabaseMissing` / `assertForbidden` / `404`).
3. **Database Transaction & Atomicity Guard:** Setiap alur bisnis yang melibatkan multi-tabel (`DB::transaction`) wajib diuji jalur kegagalannya (*exception path*) untuk memastikan bahwa jika step ke-2 gagal, step ke-1 di-rollback 100% (tidak ada data menggantung / corrupt).
4. **Edge Cases & Number Precision:** Uji nilai batas secara eksplisit: kuantitas 0, angka minus, desimal ekstrem pada HPP/harga, pembagian dengan nol (*division by zero*), dan stok habis.
5. **Side-Effect Assertion:** Verifikasi bahwa event, background job, dan notifikasi yang dipicu memiliki parameter/recipient yang tepat menggunakan method assertion Laravel Fakes (misal: `Notification::assertSentTo(...)`).

---

## 4. Protokol Wajib: Open Question Skenario Pengujian (Test Case Alignment)
Saat merancang implementasi fitur baru (*build*) maupun pengembangan (*enhancement*):
- **WAJIB** mengajukan pertanyaan terbuka (*open question*) atau menyajikan matriks skenario test yang direncanakan kepada user/stakeholder untuk diselaraskan sebelum mengeksekusi testing.
- Matriks skenario uji minimal mencakup:
  1. *Happy Path* (alur sukses standar & variasi input valid).
  2. *Edge Cases* (nilai batas, 0, desimal ekstrem, stok habis, concurrent/race condition).
  3. *Validation & Exception Path* (validasi form gagal, saldo tidak cukup, status transisi ilegal).
  4. *Multi-Tenant Isolation & Authorization* (pencegahan kebocoran data antar tenant, role permission check).
  5. *Cross-Domain Side Effects* (ledger mutasi stok, pencatatan audit log, dispatch notifikasi/mail).

---

## 5. Definition of Done (DoD)
Tugas dinyatakan selesai (*Done*) HANYA JIKA:
1. Linter PHP & Frontend bersih (`vendor/bin/pint --dirty` dan `npm run lint`).
2. Seluruh automated test terkait (Unit, Feature, Integration, Regression) lolos (`100% passing`).
3. Skenario test case telah dikonfirmasi dan mencakup jalur kritis bisnis serta multi-tenant scoping.
4. Verifikasi dua arah migrasi database (`migrate -> rollback -> migrate`) berhasil tanpa error jika ada perubahan skema database (Rule 08).
5. Tidak ada error/warning console pada antarmuka frontend.
