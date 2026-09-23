# Rule 06: Standar MCP Tooling & Penegakan Pengujian (Testing)

## 1. Standarisasi Penggunaan MCP (Model Context Protocol)
- **Git MCP:** Prioritaskan alat Git MCP (`status`, `add`, `commit`, `checkout`, `stash_save`, `stash_pop`) dibandingkan terminal raw git.
- **Conventional Commits:** `feat(scope): ...`, `fix(scope): ...`, `refactor(scope): ...`, `style: ...`, `test(scope): ...`, `chore: ...`. Commit hanya saat kode terverifikasi bersih.
- **PostgreSQL Core MCP (`sollu-db`):** **HANYA QUERY READ-ONLY (`SELECT`).** DILARANG KERAS mutasi skema (`ALTER`, `DROP`) atau data (`INSERT`, `UPDATE`, `DELETE`) langsung via MCP.
- **Sollu Project Inspector MCP (`sollu-project`):** Manfaatkan `check_enum_integrity`, `trace_feature_stack`, `audit_tenant_isolation`, `inspect_inventory_state`, dan `lint_agent_rules`.

---

## 2. Penegakan Pengujian Otomatis (Testing Enforcement)
Setiap penambahan fitur atau modifikasi logika bisnis **WAJIB** disertai penambahan/pembaruan automated tests:
1. **Unit Tests Service (100% Mocking):**
   - Lokasi: `tests/Unit/Services/{Module}/`
   - Logika kalkulasi, HPP/COGS, diskon, dan algoritma WAJIB diuji tanpa dependensi database eksternal (`sqlite:memory` atau mock service).
2. **Feature & Tenant Isolation Tests:**
   - Lokasi: `tests/Feature/`
   - Menguji isolasi multi-tenant (`business_id`), otorisasi ganda (`v-can` & `v-feature`), dan validasi Form Request.
3. **Eksekusi Pengujian:**
   - Jalankan test spesifik: `php artisan test --compact --filter=NamaTest`. Rerun test setelah setiap perubahan.

---

## 3. Definition of Done (DoD)
Tugas dinyatakan selesai (*Done*) HANYA JIKA:
1. Linter PHP & Frontend bersih (`vendor/bin/pint --dirty` dan `npm run lint`).
2. Seluruh automated test terkait lolos (`100% passing`).
3. Tidak ada error/warning console pada antarmuka frontend.
