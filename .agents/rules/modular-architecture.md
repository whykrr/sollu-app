---
trigger: always_on
---

# Rule: Arsitektur Modular Monolith

**1. Bounded Contexts**
Aplikasi dibagi dalam modul mandiri (Inventory, Master, Sales, dll). Setiap modul harus bisa diuji & direfaktor tanpa memecahkan modul lain.

**2. 🚨 Larangan Keras (Anti-Patterns)**

- **Mutasi Lintas Modul:** Modul A DILARANG keras melakukan insert/update/delete langsung ke tabel/Model Modul B. Gunakan _Domain Event_ atau panggil _Public Service_.
- **Import UI Lintas Modul:** Vue file Modul A dilarang mengimpor komponen private (`/Components/`) dari dalam folder Modul B. Pindahkan ke `@/Components/` (global) jika dipakai bersama.
- **God Service:** DILARANG membuat Service yang mengurus logika lintas domain.
- **Routing Berantakan:** Route WAJIB dikelompokkan di `routes/app/{module}.php`. Dilarang menaruh di `routes/app.php` langsung.
- **Over-Fetching di Index:** DILARANG me-load relasi berat (children, items, recipe, logs) atau master lookup massal di `index()` Inertia. Data detail lengkap dan opsi form WAJIB dimuat secara on-demand via endpoint `show()` atau async API saat PopUp/drawer dibuka.

**3. Komunikasi Antar Modul**

- **Events (Rekomendasi Utama):** Modul A emit `Event`, Modul B tangkap via `Listener` dan modifikasi datanya sendiri (untuk _side effects_).
- **Public Service:** Untuk kalkulasi sinkron. Modul tujuan harus return data primitif/DTO, BUKAN Query Builder mentah atau Model yang kotor (dirty state).
- **Reports (Read-only):** Hanya modul analitik yang boleh baca data (query) lintas modul, DILARANG ada mutasi.

**4. Struktur Anatomi File**

- **Backend:** `Controllers`, `Services`, `Events`, `Listeners`, `Models`, dan `Requests` diletakkan dalam namespace `App\...\App\{Module}`.
- **Frontend:** `resources/js/Pages/App/{Module}/Components` bersifat PRIVAT untuk modul itu saja (e.g. `{Entity}FormPopUp.vue`, `{Entity}DetailPopUp.vue`, `{Entity}Filter.vue`). Kontrol filter WAJIB diekstrak ke komponen privat modul ini.
- **Testing:** Unit test Service WAJIB 100% Mocking (`tests/Unit/Services/{Module}`).
