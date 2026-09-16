---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Formulir `@/Components/Form/`

Saat membuat atau mengedit formulir di seluruh aplikasi Sollu App, Anda **WAJIB** mematuhi aturan berikut:

---

## 1. 🚨 Dilarang Menggunakan Tag HTML Mentah
DILARANG KERAS menuliskan tag `<input>`, `<select>`, atau `<textarea>` mentah. Gunakan selalu komponen resmi di bawah ini:

| Komponen | Kegunaan Utama | Contoh Penggunaan |
| :--- | :--- | :--- |
| **`TextField`** | Input teks umum (`text`, `email`, `tel`, `url`) | `<TextField v-model="form.name" label="Nama Barang" :feedback="form.errors.name" />` |
| **`TextareaField`** | Area teks multiline | `<TextareaField v-model="form.notes" label="Catatan" rows="3" />` |
| **`NumberField`** | Input numerik / mata uang dengan auto format | `<NumberField v-model="form.price" label="Harga Jual" prefix="Rp" />` |
| **`PasswordField`** | Kata sandi dengan toggle intip | `<PasswordField v-model="form.password" label="Kata Sandi" />` |
| **`PinField`** | PIN angka terproteksi | `<PinField v-model="form.pin" label="PIN Otorisasi Kasir" />` |
| **`DropdownField`** | Dropdown pilihan statis atau enum | `<DropdownField v-model="form.status" :options="getOptions('StatusEnum')" label="Status" />` |
| **`AsyncSelectField`** | Dropdown pencarian async untuk data besar | `<AsyncSelectField v-model="form.item_id" endpoint="/api/items/search" label="Pilih Bahan" />` |
| **`AsyncOutletDropdown`** | Dropdown khusus outlet tenant | `<AsyncOutletDropdown v-model="form.outlet_id" label="Outlet" />` |
| **`Switch`** | Toggle switch boolean aktif / non-aktif | `<Switch v-model="form.is_active" label="Aktifkan Produk" />` |
| **`CheckboxField`** | Kotak centang tunggal | `<CheckboxField v-model="form.track_stock" label="Lacak Stok" />` |
| **`RadioField`** | Tombol radio tunggal | `<RadioField v-model="form.type" value="goods" label="Barang Jadi" />` |
| **`SelectionGroupField`** | Grup pilihan (Single segmented / Multi-checkbox) | `<SelectionGroupField v-model="form.categories" :options="categoryOptions" multiple show-select-all />` |
| **`QuillEditor`** | Editor Rich Text WYSIWYG | `<QuillEditor v-model="form.description" label="Deskripsi" />` |
| **`GroupTextIconField`** | Input teks dengan addon icon | `<GroupTextIconField v-model="search" :icon="faSearch" placeholder="Cari..." />` |
| **`GroupDropdownIconField`** | Dropdown dengan addon icon | `<GroupDropdownIconField v-model="outlet" :icon="faStore" :options="outletOptions" />` |

---

## 2. Pengikatan Data & Validasi
- **Two-Way Binding:** Selalu gunakan `v-model="form.field_name"`.
- **Error Feedback:** Teruskan pesan error validasi Inertia melalui `:feedback="form.errors.field_name"`. Dilarang mengikat class `is-invalid` secara manual.

---

## 3. Batasan Spacing Formulir
- **Spacing Antar-Input (Maksimal Skala 2):** Jarak antar bidang input formulir DILARANG melebihi skala 2 Tailwind (`space-y-2`, `gap-2`).
- **Margin/Padding Komponen (Maksimal Skala 3):** Jangan menambahkan padding atau margin besar di dalam container form (`p-3` maksimum).
