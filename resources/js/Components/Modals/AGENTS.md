---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Modal Spesifik `@/Components/Modals/`

Panduan modal khusus untuk alur fitur SaaS dan Impor Data:

---

## 1. Feature Locked Modal (`<FeatureLockedModal>`)

- **Fungsi:** Dialog upsell yang mengunci fitur berbayar dan memandu pengguna melakukan upgrade paket langganan.
- **Trigger:** Dipicu otomatis via directive `v-feature` pada fase capture saat elemen masked diklik, atau via flash message backend `feature_locked`.
- **Deklarasi Frontend:** Cukup gunakan directive `v-feature="$enums.FeatureEnum.NAME"` pada elemen target (tidak memerlukan pemanggilan modal imperatif manual di skrip komponen).

---

## 2. Import CSV/Excel Modal (`<ImportCsvModal>`)

- **Fungsi:** Modal dialog terintegrasi untuk mengunggah file spreadsheet Excel / CSV dan mengirimkannya ke endpoint controller impor asinkron.
- **Props:**
    - `show` (Boolean): Kontrol visibilitas modal.
    - `uploadUrl` (String): URL route tujuan upload file (e.g. `route('products.import')`).
    - `templateUrl` (String, optional): URL route untuk mengunduh template spreadsheet resmi.
- **Contoh Penggunaan:**
    ```vue
    <ImportCsvModal
        :show="showImportModal"
        :uploadUrl="route('products.import')"
        :templateUrl="route('products.import-template')"
        @close="showImportModal = false"
    />
    ```
