---
trigger: always_on
---

# Wajib Perhatikan: Standar Domain Service & Unit Testing

Saat bekerja di `app/Services`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Mandatory Service Unit Test:** Setiap kali membuat, mengubah, atau memperbarui logika Service Class, **WAJIB** membuat atau memperbarui Unit Test di `tests/Unit/Services/...`.
2. **100% Pure Mocking (No DB):** Test Service Layer **DILARANG KERAS** menyentuh database fisik. Wajib gunakan Mockery / `sqlite:memory` dan `RefreshDatabase`.
3. **100% Code Coverage:** Pastikan seluruh skenario (happy path, error path, exception) teruji 100%.
4. **Service Architecture:** Single-file Service (<= 500 baris) vs Split-file Single-Action Service (> 500 baris dengan method `execute()`).
5. **Modular Bounded Context & Zero Direct Cross-Table Mutation:** Dilarang melakukan mutasi database langsung ke tabel modul lain (`.agents/rules/modular-architecture.md`). Gunakan Domain Event atau Public Service Contract.
6. **Database Transactions:** Bungkus mutasi multi-tabel dalam `DB::transaction(function () { ... });`.
7. **Query & Eloquent Optimization:** Hindari query N+1 (wajib eager loading), gunakan `exists()` alih-alih `count() > 0`, gunakan batch `insert()` / `upsert()` untuk manipulasi data banyak, dan cegah query tak berbatas (`get()` tanpa limit pada data dinamis).
8. **Formatting & Testing:** Wajib jalankan `vendor/bin/pint` dan `vendor/bin/phpunit`.
9. **Debugging & Exception Tracing:** Jika Service mengalami error runtime atau unhandled exception saat pengujian, manfaatkan MCP tool `laravel-boost` (`LastError` / `ReadLogEntries`) untuk segera membaca stack trace, serta `SearchDocs` jika membutuhkan referensi API framework.

