---
trigger: always_on
---

# Wajib Perhatikan: Standar Frontend Vue 3 / Inertia / Tailwind v4

Saat bekerja di `resources/js`, Anda **WAJIB** menerapkan standar dari skill `sollu`:

1. **Komponen Form Standar:** Dilarang menggunakan raw HTML form inputs. Wajib gunakan `@/Components/Form/` (`TextField`, `DropdownField`, `SelectionGroupField`, `Switch`, dll).
2. **Layout & Drawer Mandatori:** Selalu gunakan `<MainPage>` untuk halaman utama, dan `<PopUpPage>` / `usePopUpStore()` untuk drawer formulir/sub-page (Create/Edit/Detail).
3. **Modal Konfirmasi:** Gunakan `<Modal>` / `useModalStore()` HANYA untuk dialog konfirmasi hapus/arsip singkat.
4. **Form Spacing Limit:** Spacing antar-input formulir DILARANG melebihi scale 2 (`space-y-2`, `gap-2`).
5. **Isolasi UI Modular (No Cross-Module Private Imports):** Dilarang meng-import komponen drawer/modal privat antar folder `Pages/App/{ModuleA}` dan `{ModuleB}` (`.agents/rules/modular-architecture.md`). Komponen bersama wajib berada di `@/Components/`.
6. **Otorisasi Hak Akses (RBAC Permission):** Gunakan directive `v-can="'permission.name'"` atau composable `@/Composable/useAuth` (`can('permission.name')`).
7. **Otorisasi Fitur Paket (Feature Plan):** Gunakan directive `v-feature="$enums.FeatureEnum.FEATURE_NAME"` (sembunyikan jika tidak berhak) atau `v-feature.lock="$enums.FeatureEnum.FEATURE_NAME"` (tampilkan terkunci & memicu upgrade modal), atau composable `@/Composable/usePlanFeature` (`hasFeature()`, `requireFeature()`) bersama `@/Composable/useEnum` (`enums.FeatureEnum`). Dilarang mencampuradukkan pengecekan permission dengan feature plan.
8. **Linter & Build:** Wajib jalankan `npm run fix:eslint` dan `npm run build` sebelum menyelesaikan tugas.
9. **Remove Dead Code:** Jika terdapat perubahan atau improvement pada file vue, pastikan kode yang tidak digunakan dihapus.

