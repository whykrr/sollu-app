# Rule: Standar Changelog & Git Version Tagging

Dokumen ini mendefinisikan aturan baku pembuatan, pemeliharaan, dan format file `CHANGELOG.md` serta manajemen Git Version Tagging di **Sollu App**.

---

## 1. Prinsip Utama (Core Principles)

1. **Keep a Changelog Standard:** Struktur changelog wajib mematuhi standar [Keep a Changelog v1.1.0](https://keepachangelog.com/).
2. **Semantic Versioning (SemVer):** Penomoran versi wajib mengikuti format `vMAJOR.MINOR.PATCH` (contoh: `v1.2.3`) atau format pre-release `vMAJOR.MINOR.PATCH-beta.X` / `vMAJOR.MINOR.PATCH-rc.X`.
3. **Berorientasi Manusia (Human-Readable):** Changelog ditujukan untuk manusia (developer, user, stakeholder), BUKAN sekadar salinan mentah (*raw dump*) git commit log.
4. **Git Tag sebagai Single Source of Truth (SSOT):** Setiap heading rilis pada `CHANGELOG.md` WAJIB memiliki pasangan Git Tag beranotasi yang sah di repositori.
5. **Section `[Unreleased]` Wajib Ada:** Perubahan yang telah di-commit ke branch utama tetapi belum di-tag ke versi rilis baru wajib dicatat di bawah section `## [Unreleased]`.

---

## 2. Kategori Perubahan Terstandarisasi

Setiap versi rilis dibagi ke dalam sub-kategori baku berikut (hanya tampilkan sub-kategori yang memiliki item):

| Kategori | Definisi | Tipe Conventional Commit Terkait |
| :--- | :--- | :--- |
| **`Added`** | Fitur baru, kapabilitas baru, endpoint baru, atau komponen UI baru. | `feat: ...`, `feat(scope): ...` |
| **`Changed`** | Perubahan/peningkatan fungsionalitas yang sudah ada, refactoring, optimasi performa, atau pembaruan UI/UX. | `refactor: ...`, `perf: ...`, `style: ...` |
| **`Deprecated`** | Fitur atau API yang masih berfungsi namun direncanakan dihapus pada rilis mendatang. | `feat!: ...` (deprecation notice), `refactor: deprecate ...` |
| **`Removed`** | Fitur, dependensi, modul, atau endpoint yang telah resmi dihapus dari sistem. | `refactor!: remove ...`, `chore: drop ...` |
| **`Fixed`** | Perbaikan bug, validasi, layout visual, atau kalkulasi error. | `fix: ...`, `fix(scope): ...` |
| **`Security`** | Penanganan kerentanan keamanan, perbaikan otentikasi/otorisasi, enkripsi, atau patch dependensi kritis. | `fix(auth): ...`, `sec: ...` |

> [!NOTE]
> Perubahan internal yang tidak berdampak langsung ke fungsionalitas aplikasi (misal `test:`, `docs:`, tooling konfigurasi CI/Linter internal tanpa efek runtime) dapat dikelompokkan ke dalam kategori **`Internal`** / **`Maintenance`** atau diabaikan jika tidak esensial bagi rilis produksi.

---

## 3. Ekstraksi Commit Antar Git Tag

Untuk menyusun rilis baru atau mengaudit commit antara dua tag versi, gunakan perintah git log:

```bash
# 1. Menampilkan ringkasan commit antar dua tag
git log <tag_sebelumnya>..<tag_baru> --pretty=format:"* %s (%h)"

# Contoh: Dari v1.2.2 menuju v1.2.3
git log v1.2.2..v1.2.3 --pretty=format:"* %s (%h)"

# 2. Menampilkan commit yang belum dirilis sejak tag terakhir
git log $(git describe --tags --abbrev=0)..HEAD --pretty=format:"* %s (%h)"
```

### Aturan Kurasi Commit:
- **Filtering Commit Noise:** Abaikan commit eksperimen lokal, revert sementara, atau commit perbaikan typo internal yang sudah diselesaikan sebelum rilis.
- **Formulasi Kalimat:** Gunakan kalimat aktif dan jelas dalam Bahasa Indonesia atau Bahasa Inggris konsisten. Jelaskan *dampak bisnis/teknis*, bukan sekadar nama file.
- **Cantumkan Scope & PR/Issue:** Jika berlaku, sertakan modul scope atau nomor PR/issue (misal: `* **Billing:** Tambahkan validasi perpanjangan paket otomatis (#142)`).

---

## 4. Format Struktur File `CHANGELOG.md`

```markdown
# Changelog

Semua perubahan penting pada proyek **Sollu App** didokumentasikan di file ini.
Format ini berbasis pada [Keep a Changelog](https://keepachangelog.com/id/1.1.0/), dan proyek ini mematuhi [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased]

### Added
- **Shift Management:** Peningkatan antarmuka shift kasir dan riwayat operasional harian.

### Changed
- **MCP Ecosystem:** Pembaruan konfigurasi MCP tooling untuk standardisasi Git & Database tool.

---

## [v1.2.3] - 2026-03-15

### Changed
- **Routing & Client:** Simplifikasi integrasi ZiggyVue dengan menghapus konfigurasi duplikat yang tidak digunakan.

---

## [v1.2.2] - 2026-03-10

### Added
- **Monitoring:** Endpoint pemeriksaan kesehatan sistem (`/health`) beserta automated health tests.

---

## [v1.2.1] - 2026-03-01

### Added
- **Merchant Impersonation:** Route impersonation untuk merchant dan komponen drawer detail bisnis.
- **Routing:** Restrukturisasi route app dan cockpit dengan namespace `App`.

### Fixed
- **Auth:** Perbaikan layout login pada guard Cockpit.
```

---

## 5. Standar Semantic Versioning (SemVer) di Sollu App

1. **MAJOR (`vX.0.0`):** Perubahan arsitektur besar, *breaking changes* pada database schema publik/API, atau perombakan modul fundamental yang tidak *backward-compatible*.
2. **MINOR (`v1.X.0`):** Penambahan modul fitur baru (misal: Modul Promosi, Shift Kasir, Subscription Billing) yang tetap kompatibel ke belakang.
3. **PATCH (`v1.0.X`):** Perbaikan bug, penyesuaian layout CSS/Vue, optimasi query, atau hotfix minor.
4. **PRE-RELEASE (`vX.Y.Z-beta.N` / `vX.Y.Z-rc.N`):** Versi uji coba staging atau testing sebelum rilis stabil.

---

## 6. Prosedur Release & Git Tagging

Ketika merilis versi baru, ikuti tahapan berikut secara berurutan:

1. **Verifikasi Kualitas:**
   - Jalankan `vendor/bin/pint --dirty` / `composer run format`
   - Jalankan `npm run lint` & `npm run format`
   - Pastikan seluruh test lolos (`php artisan test --compact`)
2. **Perbarui `CHANGELOG.md`:**
   - Pindahkan item dari `## [Unreleased]` ke section versi baru `## [vX.Y.Z] - YYYY-MM-DD`.
   - Tambahkan link referensi diff di bagian bawah file jika menggunakan link komparasi Git.
3. **Commit Perubahan Versi:**
   - Format commit: `chore(release): bump version to vX.Y.Z and update changelog`
4. **Buat Git Tag Beranotasi:**
   - Wajib gunakan tag beranotasi (`-a`):
     ```bash
     git tag -a v1.2.4 -m "Release v1.2.4: Ringkasan singkat fitur utama"
     ```
5. **Push ke Remote:**
   - Push commit dan tag secara eksplisit:
     ```bash
     git push origin master
     git push origin v1.2.4
     ```
