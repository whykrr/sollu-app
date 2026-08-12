---
name: sollu-integration-testing
description: >-
  End-to-End (E2E) and browser integration testing guidelines using Web MCP tools (browsermcp).
  MUST trigger whenever verifying user flows, testing Inertia page interactions, filling forms, testing PopUpPage drawers,
  capturing screenshots, auditing DOM snapshots, or inspecting browser console logs in Sollu App.
---

# Sollu App Web Integration & E2E Testing Standard

Standard pengujian integrasi antarmuka web (End-to-End / E2E Integration Testing) pada aplikasi **Sollu App** menggunakan MCP Web (`browsermcp`).

## 1. Prerequisites & Dev Server Verification

Sebelum melakukan pengujian browser via MCP tools:
1. Pastikan server aplikasi lokal Laravel & Vite aktif (misal: `http://localhost:8000` atau URL domain dev lokal).
2. Pastikan database seeder siap untuk lingkungan pengujian.

---

## 2. Standard E2E Testing Steps via `browsermcp`

### Step 1: Navigasi & Autentikasi User (`browser_navigate` & `browser_type`)
1. **Buka Halaman Target / Login:**
   Panggil `browser_navigate` dengan `url: "http://localhost:8000/login"`.
2. **Isi Form Kredensial:**
   Gunakan `browser_type` pada selector email/password:
   - Email field: `browser_type({ element: "input[type='email']", refIndex: ..., text: "owner@sollu.com" })`
   - Password field: `browser_type({ element: "input[type='password']", refIndex: ..., text: "password" })`
3. **Submit Login:**
   Panggil `browser_click` pada tombol login (`button[type='submit']`).

---

### Step 2: Verifikasi Alur UI Sollu App

1. **Pengujian Halaman Utama / Tabel (`<MainPage>`):**
   - Verifikasi header tabel, tombol aksi filter, dan tombol pengubah pagination.
   - Panggil `browser_type` pada komponen `<FilterSearch>` untuk menguji fitur live filter data.
2. **Pengujian Side Drawer Form (`<PopUpPage>` & `usePopUpStore`):**
   - Klik tombol **"Tambah Data"** atau **"Edit"** (`browser_click`).
   - Pastikan panel drawer `<PopUpPage>` terbuka dari sebelah kanan.
   - Pengisian bidang formulir `@/Components/Form/`:
     - Teks: `browser_type` pada `TextField` / `TextareaField`.
     - Dropdown / Select: `browser_select_option` atau `browser_click` pada `AsyncSelectField`.
     - Switch / Checkbox: `browser_click` pada `Switch`.
3. **Pengujian Sticky Footer Teleport (`#popUpFooter`):**
   - Pastikan tombol simpan (`btn-main`) ada pada footer sticky drawer.
   - Klik simpan (`browser_click`) untuk mengirim formulir data.
4. **Pengujian Modal Dialog Konfirmasi (`<Modal>` & `useModalStore`):**
   - Untuk aksi hapus / arsip data, klik tombol hapus.
   - Pastikan center modal dialog konfirmasi muncul. Klik tombol **"Ya, Hapus"** untuk mengonfirmasi.

---

### Step 3: Inspeksi DOM & Visual Screenshot

1. **Audit Struktur DOM & Accessibility (`browser_snapshot`):**
   Panggil `browser_snapshot` untuk mendapatkan gambaran elemen yang sedang aktif, ref index, dan accessibility tree.
2. **Verifikasi Visual UI & Toast Notification (`browser_screenshot`):**
   Panggil `browser_screenshot` setelah formulir disimpan untuk memverifikasi:
   - Tampilan visual layout bersih tanpa pecahan styling Tailwind v4.
   - Kehadiran notifikasi toast sukses (`useToastStore`) yang muncul di sudut layar.

---

### Step 4: Audit Error Log Konsol Browser (`browser_get_console_logs`)

Setelah alur pengujian selesai, **WAJIB** memanggil `browser_get_console_logs` untuk memastikan:
- Tidak ada *Uncaught TypeError* atau JavaScript exception pada Vue 3 / Inertia runtime.
- Tidak ada request XHR/Axios yang mengembalikan status HTTP 500 Server Error atau HTTP 422 Unhandled Validation Error.

---

## 3. Summary Cheat Sheet MCP Web Tools

| Perkakas MCP | Kegunaan Utama |
| :--- | :--- |
| `browser_navigate` | Navigasi ke URL target |
| `browser_click` | Klik tombol, link, atau elemen UI |
| `browser_type` | Pengisian teks pada bidang form |
| `browser_select_option` | Memilih item dropdown |
| `browser_press_key` | Menekan tombol keyboard (Enter, Escape, Tab) |
| `browser_snapshot` | Mengambil peta struktur DOM & ref index |
| `browser_screenshot` | Mengambil tangkapan layar UI visual |
| `browser_get_console_logs` | Memeriksa log error / console warning browser |
