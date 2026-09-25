---
trigger: always_on
---

# Wajib Perhatikan: Standar Domain Service & Unit Testing

Saat bekerja di `app/Services`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Mandatory Service Testing:** Setiap kali membuat, mengubah, atau memperbarui logika Service Class, **WAJIB** membuat atau memperbarui test di `tests/Integration/Services/...` atau `tests/Feature/Services/...` (atau `tests/Unit/` jika pure calculation tanpa Eloquent).
2. **Pragmatic Testing & Real Data Integrity:** Service yang berinteraksi dengan database WAJIB diuji menggunakan In-Memory SQLite (`RefreshDatabase`) untuk memvalidasi persistensi data, mutasi saldo/stok, foreign key constraint, dan isolasi tenant. DILARANG me-mocking query Eloquent. Gunakan Laravel Fakes (`Event::fake()`, `Queue::fake()`, `Notification::fake()`) untuk side-effects.
3. **100% Code Coverage & Transaction Rollback:** Pastikan seluruh skenario (happy path, edge cases, error path, exception & rollback transaksi) teruji 100%.
4. **Service Architecture:** Single-file Service (<= 500 baris) vs Split-file Single-Action Service (> 500 baris dengan method `execute()`).
5. **Modular Bounded Context & Zero Direct Cross-Table Mutation:** Dilarang melakukan mutasi database langsung ke tabel modul lain (`.agents/rules/03-modular-architecture.md`). Gunakan Domain Event atau Public Service Contract.
6. **Database Transactions:** Bungkus mutasi multi-tabel dalam `DB::transaction(function () { ... });`.
7. **Query & Eloquent Optimization:** Hindari query N+1 (wajib eager loading), gunakan `exists()` alih-alih `count() > 0`, gunakan batch `insert()` / `upsert()` untuk manipulasi data banyak, dan cegah query tak berbatas (`get()` tanpa limit pada data dinamis).
8. **Formatting & Testing:** Wajib jalankan `vendor/bin/pint` dan `vendor/bin/phpunit`.
9. **Debugging & Exception Tracing:** Jika Service mengalami error runtime atau unhandled exception saat pengujian, manfaatkan MCP tool `laravel-boost` (`LastError` / `ReadLogEntries`) untuk segera membaca stack trace, serta `SearchDocs` jika membutuhkan referensi API framework.

