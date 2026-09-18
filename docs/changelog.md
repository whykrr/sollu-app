# Panduan & Dokumentasi Standar Changelog Sollu App

Dokumen ini menjelaskan alur kerja, standar format, dan prosedur teknis pengelolaan riwayat perubahan (**Changelog**) berbasis **Git Tag Versioning** di repositori **Sollu App**.

---

## 1. Pendahuluan

Dokumentasi riwayat rilis adalah komponen krusial untuk melacak evolusi sistem, mempermudah deployment audit, serta memberikan transparansi bagi tim pengembang, operasional, dan pemangku kepentingan.

Sollu App mengadopsi dua standar industri:
1. **[Keep a Changelog (v1.1.0)](https://keepachangelog.com/id/1.1.0/):** Standar struktur dan klasifikasi kategori perubahan.
2. **[Semantic Versioning 2.0.0 (SemVer)](https://semver.org/lang/id/):** Format penomoran versi `vMAJOR.MINOR.PATCH`.

---

## 2. Struktur File `CHANGELOG.md`

File `CHANGELOG.md` terletak di direktori root aplikasi. Urutan entri disusun secara **kronologis terbalik** (versi terbaru di paling atas).

### Template Format Baku

```markdown
# Changelog

Semua perubahan penting pada proyek **Sollu App** didokumentasikan di file ini.
Format ini berbasis pada [Keep a Changelog](https://keepachangelog.com/id/1.1.0/), dan proyek ini mematuhi [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased]

Bagian ini menampung perubahan yang telah di-commit ke branch master/main namun belum di-tag ke versi rilis baru.

### Added
- **[Modul]:** Deskripsi fitur atau endpoint baru yang ditambahkan.

### Changed
- **[Modul]:** Perubahan fungsionalitas, refactoring, atau pembaruan UI.

### Fixed
- **[Modul]:** Perbaikan bug atau penanganan kesalahan logika.

---

## [vX.Y.Z] - YYYY-MM-DD

### Added
- ...

### Changed
- ...

### Deprecated
- ...

### Removed
- ...

### Fixed
- ...

### Security
- ...
```

---

## 3. Taksonomi Kategori Perubahan

| Sub-Heading | Ikon / Konvensi | Deskripsi & Contoh Penggunaan |
| :--- | :--- | :--- |
| **`Added`** | 🚀 Fitur Baru | Endpoint API baru, halaman Vue baru, komponen UI baru, migration skema baru. |
| **`Changed`** | 🔄 Perubahan | Pembaruan workflow bisnis, optimasi query, perombakan desain UI/UX, penyesuaian dependensi. |
| **`Deprecated`** | ⚠️ Segera Dihapus | Method/route lama yang ditandai untuk dihapus pada versi major berikutnya. |
| **`Removed`** | 🗑️ Dihapus | Kode mati (*deadcode*), route lama, tabel deprecated yang resmi didrop. |
| **`Fixed`** | 🐛 Perbaikan Bug | Perbaikan exception, kesalahan hitung pajak/diskon, race condition, glitch styling. |
| **`Security`** | 🔒 Keamanan | Patch kerentanan auth, token hardening, rate-limiting, proteksi multi-tenant isolation. |

---

## 4. Alur Kerja Git Tagging & Pembuatan Changelog

```
┌────────────────────────────────────────────────────────┐
│ 1. Kumpulkan commit antar tag via Git CLI               │
│    git log <tag_lama>..<tag_baru>                      │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ 2. Kurasi & Kelompokkan ke Kategori Keep a Changelog   │
│    Added, Changed, Fixed, Security, dll.               │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ 3. Perbarui CHANGELOG.md & Naikkan Versi               │
│    chore(release): bump version to vX.Y.Z              │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ 4. Buat Git Tag Beranotasi & Push                      │
│    git tag -a vX.Y.Z -m "Release message"              │
│    git push origin master --tags                       │
└────────────────────────────────────────────────────────┘
```

---

## 5. Perintah Praktis Git untuk Changelog

### A. Melihat Tag Terurut Berdasarkan Versi SemVer
```bash
git tag -l --sort=-v:refname
```

### B. Menampilkan Seluruh Commit Antara Dua Tag
```bash
# Menampilkan commit satu baris lengkap dengan hash dan judul
git log <tag_sebelumnya>..<tag_terbaru> --pretty=format:"* %s (%h)"

# Contoh: Commit dari v1.2.2 ke v1.2.3
git log v1.2.2..v1.2.3 --pretty=format:"* %s (%h)"
```

### C. Menampilkan Commit dari Tag Terakhir hingga HEAD (`[Unreleased]`)
```bash
# Otomatis mendeteksi tag terakhir
git log $(git describe --tags --abbrev=0)..HEAD --pretty=format:"* %s (%h)"
```

### D. Mengelompokkan Commit Berdasarkan Author atau Tipe
```bash
git log <tag_sebelumnya>..<tag_terbaru> --pretty=format:"* %s [%an]"
```

---

## 6. Contoh Pemetaan Conventional Commits ke Changelog

Berikut panduan konversi dari commit message menuju entri Changelog yang bersih dan komunikatif:

| Raw Commit Message | Kategori Changelog | Entri Changelog Terkurasi |
| :--- | :--- | :--- |
| `feat: enhance Shift management UI and functionality` | **Added** | **Shift Management:** Peningkatan antarmuka shift kasir dan riwayat operasional harian. |
| `feat(product): optimize category reorder loading state and improve UI labels` | **Changed** | **Product:** Optimasi indikator loading saat reorder kategori dan penyelarasan label antarmuka. |
| `refactor: update table header z-index and add sorting functionality` | **Changed** | **UI/Table:** Perbaikan stacking context z-index header tabel dan integrasi sortable server-side. |
| `fix: change orientation from portrait to landscape in Vite configuration` | **Fixed** | **Build/Vite:** Penyesuaian konfigurasi orientasi build asset. |
| `chore(mcp): remove mysql and browsermcp from mcp config` | *Internal / Skip* | *(Dilewati jika tidak berdampak pada runtime aplikasi)* |

---

## 7. Skrip Bantu Otomasi (Helper Script)

Untuk memudahkan generate draft changelog cepat, Anda dapat menjalankan command berikut pada terminal:

```bash
#!/usr/bin/env bash
# Script sederhana untuk mencetak draft changelog dari range git tag

FROM_TAG=${1:-$(git describe --tags --abbrev=0 2>/dev/null)}
TO_TAG=${2:-HEAD}

echo "### Commit Changelog ($FROM_TAG..$TO_TAG)"
echo ""
echo "#### Added"
git log "$FROM_TAG..$TO_TAG" --grep="^feat" --pretty=format:"- %s (%h)"
echo ""
echo "#### Changed"
git log "$FROM_TAG..$TO_TAG" --grep="^refactor\|^perf\|^style" --pretty=format:"- %s (%h)"
echo ""
echo "#### Fixed"
git log "$FROM_TAG..$TO_TAG" --grep="^fix" --pretty=format:"- %s (%h)"
```

---

## 8. Checklist Rilis (Definition of Release)

Sebelum mempublikasikan tag rilis dan memperbarui `CHANGELOG.md`:

- [ ] Seluruh kode telah diformat via `composer run format` (Laravel Pint) dan `npm run format` (Prettier).
- [ ] Linter frontend bersih tanpa error (`npm run lint`).
- [ ] Seluruh automated unit test dan feature test lolos (`php artisan test --compact`).
- [ ] File `CHANGELOG.md` telah memuat tanggal rilis baku (`YYYY-MM-DD`) dan deskripsi perubahan yang jelas.
- [ ] Git tag dibuat menggunakan flag beranotasi `-a` dan dipush ke remote repository.
