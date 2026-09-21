---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Formulir `@/Components/Form/`

Saat membuat atau mengedit formulir di seluruh aplikasi Sollu App, Anda **WAJIB** mematuhi aturan berikut:

---

## 1. 🚨 Dilarang Menggunakan Tag HTML Mentah

DILARANG KERAS menuliskan tag `<input>`, `<select>`, atau `<textarea>` mentah. Gunakan selalu komponen resmi di bawah ini:

| Komponen                      | Kegunaan Utama                                                                                                                            | Contoh Penggunaan                                                                                                     |
| :---------------------------- | :---------------------------------------------------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------------------------------------- |
| **`TextField`**               | Input teks umum (`text`, `email`, `tel`, `url`)                                                                                           | `<TextField v-model="form.name" label="Nama Barang" :feedback="form.errors.name" />`                                  |
| **`TextareaField`**           | Area teks multiline                                                                                                                       | `<TextareaField v-model="form.notes" label="Catatan" rows="3" />`                                                     |
| **`NumberField`**             | Input numerik / mata uang dengan auto format                                                                                              | `<NumberField v-model="form.price" label="Harga Jual" prefix="Rp" />`                                                 |
| **`PasswordField`**           | Kata sandi dengan toggle intip                                                                                                            | `<PasswordField v-model="form.password" label="Kata Sandi" />`                                                        |
| **`PinField`**                | PIN angka terproteksi                                                                                                                     | `<PinField v-model="form.pin" label="PIN Otorisasi Kasir" />`                                                         |
| **`DropdownField`**           | Dropdown pilihan statis/enum ringkas ($\le 5$ opsi)                                                                                       | `<DropdownField v-model="form.status" :options="getOptions('StatusEnum')" label="Status" />`                          |
| **`SearchableDropdownField`** | **Standar Wajib** dropdown master data / banyak data ($> 5$ opsi) dengan search & floating popover (UOM, Supplier, Kategori, Outlet, dll) | `<SearchableDropdownField v-model="form.uom_id" :options="uomOptions" label="Satuan" :searchable="true" size="sm" />` |
| **`AsyncSelectField`**        | Dropdown pencarian async server-side untuk data masif ribuan baris                                                                        | `<AsyncSelectField v-model="form.item_id" endpoint="/api/items/search" label="Pilih Bahan" />`                        |
| **`AsyncOutletDropdown`**     | Dropdown khusus outlet tenant                                                                                                             | `<AsyncOutletDropdown v-model="form.outlet_id" label="Outlet" />`                                                     |
| **`Switch`**                  | Toggle switch boolean aktif / non-aktif                                                                                                   | `<Switch v-model="form.is_active" label="Aktifkan Produk" />`                                                         |
| **`CheckboxField`**           | Kotak centang tunggal                                                                                                                     | `<CheckboxField v-model="form.track_stock" label="Lacak Stok" />`                                                     |
| **`RadioField`**              | Tombol radio tunggal                                                                                                                      | `<RadioField v-model="form.type" value="goods" label="Barang Jadi" />`                                                |
| **`SelectionGroupField`**     | Grup pilihan (Single segmented / Multi-checkbox)                                                                                          | `<SelectionGroupField v-model="form.categories" :options="categoryOptions" multiple show-select-all />`               |
| **`QuillEditor`**             | Editor Rich Text WYSIWYG                                                                                                                  | `<QuillEditor v-model="form.description" label="Deskripsi" />`                                                        |
| **`DisclosureSection`**       | Collapsible section untuk opsi lanjutan (Progressive Disclosure)                                                                          | `<DisclosureSection title="Opsi Lanjutan" :badge="activeCount"><TextField ... /></DisclosureSection>`                 |
| **`FormStepper`**             | Visual Stepper indikator untuk Create Wizard (Tier 3)                                                                                     | `<FormStepper :steps="steps" v-model:current-step-index="step" :errors="form.errors" />`                              |
| **`FormTabs`**                | Tab navigation untuk Edit Form (Tier 3)                                                                                                   | `<FormTabs :tabs="tabs" v-model="activeTab" :errors="form.errors" />`                                                 |

---

## 2. 🏛️ Klasifikasi Formulir (3-Tier Form Architecture)

Untuk mencegah pengguna merasa _overwhelm_ saat mengisi formulir, terapkan klasifikasi berikut:

### Tier 1: Simple / Quick Form (≤ 5 Fields)

- **Karakteristik:** Formulir cepat, flat vertikal tanpa collapsible/stepper.
- **Contoh:** Kategori, Satuan UOM, Meja Kasir, Alasan Void.

### Tier 2: Progressive Disclosure Form (6 – 12 Fields, Single Domain)

- **Prinsip 80/20 (Core vs Advanced):**
    - **Core Fields (80% Operasional Harian):** Wajib langsung terlihat di bagian atas (Nama, Harga, Kategori, Satuan).
    - **Advanced Fields (20% Opsi Tambahan):** WAJIB dibungkus dalam `<DisclosureSection>` collapsible (SKU kustom, Barcode manual, Alert stok minimum, Tag, Catatan panjang).
- **Trigger-based / Conditional Reveal:** Field dependen dilarang dirender jika toggle/pemicunya tidak aktif (misal: field _Minimum Stok_ hanya muncul jika toggle _Lacak Stok_ aktif).
- **Contoh:** Bahan Baku (Raw Material), Pelanggan (Customer), Karyawan, Promo Diskon.

### Tier 3: Complex Wizard & Tabbed Form (> 12 Fields atau Multi-Domain)

- **Pola Asimetris (Create vs Edit):**
    - **Mode Tambah / Create:** WAJIB gunakan **Linear Stepper** (`<FormStepper>`) memandu langkah demi langkah (Step 1 → Step 2 → Step 3).
    - **Mode Ubah / Edit:** WAJIB gunakan **Direct Tabbed Navigation** (`<FormTabs>`) agar pengguna dapat langsung menuju bagian yang ingin disunting tanpa harus mengklik _next-next_.
- **Contoh:** Produk Komprehensif (Info Dasar, Varian & Resep, Stok & Outlet), Purchase Order (Header, Detail Items, Ringkasan Biaya), Transfer Stok Antar-Outlet.

---

## 3. Pengikatan Data, Validasi & Error State

- **Two-Way Binding:** Selalu gunakan `v-model="form.field_name"`.
- **Error Feedback:** Teruskan pesan error validasi Inertia melalui `:feedback="form.errors.field_name"`. Dilarang mengikat class `is-invalid` secara manual.
- **Auto-Open Collapsible on Error:** `<DisclosureSection>` secara otomatis terbuka jika terdapat error validasi pada field di dalamnya.
- **Error Badging pada Stepper / Tabs:** Teruskan `:errors="form.errors"` ke `<FormStepper>` atau `<FormTabs>` agar step/tab yang bermasalah menampilkan indikator titik merah (_error dot_).

---

## 4. Batasan Spacing & Ergonomi Drawer (`PopUpPage`)

- **Spacing Antar-Input (Maksimal Skala 2):** Jarak antar bidang input formulir DILARANG melebihi skala 2 Tailwind (`space-y-2`, `gap-2`).
- **Margin/Padding Komponen (Maksimal Skala 3):** Jangan menambahkan padding atau margin besar di dalam container form (`p-3` maksimum).
- **Sticky Footer Actions (`#popUpFooter`):** Seluruh tombol aksi form (Batal, Simpan, Kembali, Lanjut) WAJIB di-teleport ke `#popUpFooter` agar berada di _Thumb Zone_ bawah yang ergonomis.
