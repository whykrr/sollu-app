# Rule 07: Git Workflow, Semantic Versioning & Standar Changelog

## 1. Prinsip Utama Changelog & Versi
1. **Keep a Changelog Standard:** Format `CHANGELOG.md` mematuhi [Keep a Changelog v1.1.0](https://keepachangelog.com/).
2. **Section `[Unreleased]` Wajib Ada:** Perubahan yang sudah di-commit tapi belum dirilis wajib dicatat di bawah `## [Unreleased]`.
3. **Semantic Versioning (SemVer):** Format `vMAJOR.MINOR.PATCH` (contoh: `v1.2.3`).
   - `MAJOR` (`vX.0.0`): Breaking changes arsitektur/database.
   - `MINOR` (`v1.X.0`): Fitur/modul baru backward-compatible.
   - `PATCH` (`v1.0.X`): Perbaikan bug/hotfix minor.
4. **Git Tag SSOT & Baseline Produksi:** Setiap heading rilis pada `CHANGELOG.md` **WAJIB** berpasangan dengan Git Tag beranotasi (`git tag -a vX.Y.Z -m "..."`). Tag rilis stabil ini menjadi **parameter baseline produksi** yang secara mutlak mengunci (*freeze*) seluruh file database migrasi yang ada di dalamnya (Rule 08).

---

## 2. Kategori Perubahan Terstandarisasi
Setiap versi rilis hanya memuat sub-kategori aktif:
- **`Added`**: Fitur/komponen/endpoint baru (`feat: ...`).
- **`Changed`**: Modifikasi fungsionalitas, refaktor, optimasi (`refactor: ...`, `perf: ...`).
- **`Deprecated`**: Fitur yang direncanakan dihapus mendatang.
- **`Removed`**: Fitur/modul yang resmi dihapus (`refactor!: remove ...`).
- **`Fixed`**: Perbaikan bug/validasi/layout (`fix: ...`).
- **`Security`**: Patch kerentanan keamanan/otorisasi (`fix(auth): ...`).

---

## 3. Prosedur Rilis Cepat & Verifikasi Tag
1. **Verifikasi Kualitas & Integritas:**
   - Linter & Formatting: `vendor/bin/pint --dirty`, `npm run lint`.
   - Test Suite: `php artisan test --compact`.
   - **Audit Migrasi & Backward Compatibility (MCP):** Jalankan tool MCP `audit_migration_compatibility` untuk memastikan tidak ada file migrasi dari tag sebelumnya yang diubah atau dihapus.
2. Pindahkan item dari `## [Unreleased]` ke heading versi baru `## [vX.Y.Z] - YYYY-MM-DD`.
3. Commit: `chore(release): bump version to vX.Y.Z and update changelog`.
4. Tag & Push: `git tag -a vX.Y.Z -m "Release vX.Y.Z"` lalu `git push origin master --tags`.
5. **Kunci Migrasi:** Seluruh migrasi pada tag rilis tersebut kini terkunci permanen. Seluruh penyesuaian skema berikutnya wajib membuat file migrasi baru (`php artisan make:migration`).
