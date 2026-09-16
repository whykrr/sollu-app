---
trigger: always_on
---

# Wajib Perhatikan: Standar Controller Backend Cockpit

Saat bekerja di `app/Http/Controllers/Cockpit`, Anda **WAJIB** menerapkan standar berikut:

1. **Domain Sub-Foldering Pattern:** Seluruh Controller Cockpit WAJIB diletakkan di dalam sub-folder spesifik domain data (misal: `app/Http/Controllers/Cockpit/Auth/ProfileController.php`, `app/Http/Controllers/Cockpit/Merchant/BusinessController.php`, dll).
2. **FormRequest Domain Isolation:** Seluruh FormRequest validasi Cockpit WAJIB diletakkan di dalam sub-folder domain yang relevan di `app/Http/Requests/Cockpit/{Domain}/{RequestName}.php`.
3. **Thin Controller Pattern:** Logika bisnis kompleks wajib di-offload ke Service Class di `app/Services/Cockpit/`.
4. **No Hardcoded Controller Messages:** Dilarang keras menuliskan pesan respon manual. Wajib menggunakan `App\Constants\ResourceMessage` dan `App\Constants\FlashDataVariable`.
5. **Formatting:** Wajib jalankan `vendor/bin/pint` sebelum menyelesaikan tugas backend.
