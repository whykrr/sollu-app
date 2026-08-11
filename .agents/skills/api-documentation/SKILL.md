---
name: sollu-api-documentation
description: Aturan untuk memperbarui dokumentasi API (Postman / Swagger) setiap kali ada perubahan pada struktur request atau response API di proyek Sollu. Trigger skill ini saat memodifikasi API Endpoint.
---

# Sollu API Documentation Maintenance

## Tujuan
Dokumentasi API adalah kontrak antara Backend dan Frontend/Client. Setiap kali terjadi perubahan pada kode yang memengaruhi *request* (parameter, body, header) atau *response* (struktur JSON, tipe data, HTTP status) API, kamu **DIWAJIBKAN** untuk memperbarui dokumentasi API yang relevan agar selalu *up-to-date*.

## Aturan Pelaksanaan
1. **Deteksi Perubahan API**: 
   Trigger skill ini jika kamu melakukan modifikasi pada:
   - File `routes/api.php` (menambah/menghapus *endpoint*).
   - Class `FormRequest` (menambah validasi, *field* baru).
   - Pengembalian JSON di `Controller` atau `Resource` (menambah atribut, menyembunyikan relasi, dll).

2. **Perbarui File Dokumentasi**: 
   Cari dan perbarui file dokumentasi API yang ada di dalam proyek:
   - **Postman Collection**: Cari file `.json` yang mendeskripsikan koleksi Postman (contoh: `docs/postman_collection.json` atau serupa).
   - **Swagger / OpenAPI**: Cari file anotasi Swagger atau spesifikasi `.yaml`/`.json` jika ada.

3. **Detail yang Harus Diperbarui**:
   - Jika ada kolom (parameter/body) baru, tambahkan ke dokumentasi beserta deskripsi dan tipe datanya.
   - Jika struktur *response* (balasan JSON) berubah, perbarui contoh balasan (*example response*) di Postman/Swagger.
   - Perbarui tipe data (*integer*, *string*, *boolean*) jika ada perubahan aturan (contoh: UUID dari sebelumnya BigInt).

4. **Konfirmasi & Peringatan**: 
   Jika file dokumentasi tidak dapat kamu temukan secara mandiri di repositori, kamu **wajib** memberi tahu *user* untuk memberikan lokasi file dokumentasi tersebut sebelum kamu mengakhiri tugas, agar kamu bisa segera memperbaruinya.
