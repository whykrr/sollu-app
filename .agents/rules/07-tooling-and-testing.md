---
trigger: always_on
---

# Rule 07: Standar MCP Tooling & Penegakan Pengujian (Testing)

## 1. Standarisasi Penggunaan MCP (Model Context Protocol)

Ruang kerja Sollu App telah dilengkapi dengan MCP servers yang terkonfigurasi. AI Agent **WAJIB** memprioritaskan penggunaan alat-alat MCP berikut dibandingkan eksekusi terminal mentah:

### A. Git MCP (`git-mcp-server`)
- **MANDATORY:** Prioritaskan pemanggilan MCP Git (`status`, `add`, `commit`, `branch_list`, `branch_create`, `checkout`, `stash_save`, `stash_pop`) dibandingkan raw shell command `git` via terminal runner.
- **Standar Pesan Commit (Conventional Commits):** Format pesan commit WAJIB terstruktur:
  - `feat(module): deskripsi fitur baru`
  - `fix(module): perbaikan bug spesifik`
  - `refactor(module): refaktor kode tanpa mengubah fungsionalitas/behavior`
  - `style: perbaikan linter atau penyesuaian format tampilan`
  - `test(module): penambahan atau pembaruan unit/feature test`
  - `chore: pembaruan dependensi, konfigurasi, atau rilis versi`
- **Atomic & Verified Commits:** Commit HANYA dilakukan setelah kode melewati verifikasi (linter `pint`/`eslint` bersih, test lolos). **DILARANG** melakukan commit kode setengah jadi atau dalam kondisi rusak/error.
- **Safe Working Tree:** Selalu verifikasi `status` sebelum checkout atau merge. Amankan perubahan yang belum selesai menggunakan `stash_save` ketimbang membuangnya.

### B. Laravel Boost MCP (`laravel-boost`)
- **Inspeksi Skema Database:** Gunakan `database-schema` untuk memeriksa struktur kolom riil, tipe data, foreign key, dan indeks tabel sebelum membuat migration atau query Eloquent.
- **Troubleshooting Cepat:** Gunakan `last-error` dan `read-log-entries` saat terjadi error API untuk menganalisis jejak stacktrace Laravel tanpa membuang context window membaca log mentah.
- **Dokumentasi Resmi:** Gunakan `search-docs` untuk mencari panduan/API resmi ekosistem Laravel & Inertia.

### C. PostgreSQL Core MCP (`sollu-db`)
- **Validasi Data Riil:** Gunakan `sollu-db` untuk memeriksa data riil, memvalidasi isolasi multi-tenant (`business_id`, `outlet_id`), foreign key integrity, dan verifikasi hasil seeding.
- **🚨 ATURAN KETAT READ-ONLY (SELECT ONLY):**
  - MCP Postgres **HANYA** boleh menjalankan query pembacaan (`SELECT`).
  - **DILARANG KERAS** mengeksekusi mutasi skema (`CREATE`, `ALTER`, `DROP`) atau mutasi data (`INSERT`, `UPDATE`, `DELETE`) secara langsung via MCP.
  - Seluruh mutasi skema **WAJIB** melalui Laravel Migration, dan mutasi data **WAJIB** melalui Service/Model/Seeder aplikasi.

---

## 2. Penegakan Pengujian Otomatis (Testing Enforcement)

Setiap penambahan fitur atau modifikasi logika bisnis **WAJIB** disertai dengan penambahan atau pembaruan test otomatis.

### A. Hirarki & Standar Pengujian
1. **Unit Tests Service (100% Mocking):**
   - Lokasi: `tests/Unit/Services/{Module}/`
   - Logika kalkulasi bisnis, perhitungan HPP/COGS, diskon, dan algoritma **WAJIB** diuji tanpa ketergantungan database eksternal lambat (`sqlite:memory` atau mock service).
2. **Feature & Isolation Tests:**
   - Lokasi: `tests/Feature/`
   - Menguji isolasi multi-tenant (`business_id` tidak boleh bocor ke tenant lain), otorisasi ganda (RBAC permission & SaaS plan feature gating), dan validasi Form Request.
3. **Database & Factories:**
   - Gunakan Model Factory bawaan untuk menyiapkan data uji. Manfaatkan custom states pada factory daripada menyusun state manual.

### B. Eksekusi Pengujian
- Jalankan test spesifik yang relevan dengan perubahan:
  ```bash
  php artisan test --compact --filter=NamaTest
  ```
- Rerun test setelah setiap perubahan kode untuk memastikan tidak terjadi regresi.
- Dilarang membuat skrip verifikasi ad-hoc jika pengujian dapat ditangani melalui automated unit/feature tests.

### C. Definition of Done (DoD)
Sebuah tugas atau refactoring dinyatakan selesai (*Done*) HANYA JIKA:
1. Kode telah diformat bersih via `vendor/bin/pint --dirty` dan `npm run lint`.
2. Seluruh test terkait lolos tanpa kegagalan (`100% passing`).
3. Tidak ada error/warning console pada antarmuka frontend.
