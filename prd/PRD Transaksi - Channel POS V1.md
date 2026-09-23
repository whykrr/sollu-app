# PRD — Transaksi & Penjualan - Channel POS App (V1)

## 1. Overview

Modul Transaksi & Penjualan POS (Point of Sale) adalah jantung dari operasional aplikasi kasir **Sollu POS Client** yang dikembangkan menggunakan **Flutter**. Modul ini didesain secara khusus untuk melayani penjualan ritel dan F&B langsung di *outlet* dengan mobilitas tinggi, antarmuka yang sangat responsif, dan ketergantungan minimal terhadap stabilitas koneksi internet.

**Selling Point Utama V1:**
- **Arsitektur Offline-First Sejati**: Dibangun di atas database lokal (Drift/SQLite), kasir dapat memproses ribuan transaksi tanpa hambatan meski koneksi internet terputus. Sistem secara otomatis akan melakukan sinkronisasi latar belakang (*background queue sync*) ke *backend* Laravel ketika internet kembali tersedia.
- **Fleksibilitas Alur Kasir (Configurable)**: Mengerti kultur bisnis UMKM Indonesia, aplikasi ini mendukung **Kasir Tanpa Shift** (untuk warung/toko kelontong sederhana) maupun *Shift* ketat (untuk restoran/retail besar). Kasir juga dapat melakukan *Override Harga* sesuka hati, memberikan diskon kustom manual, hingga mengizinkan transaksi saat stok sedang kosong (jika dikonfigurasi demikian).
- **Manajemen Kas (Cash Management)**: Selain transaksi jual beli, kasir dapat mencatat *Cash In* (Modal/Kas masuk) dan *Cash Out* (Kas keluar untuk operasional harian) yang terintegrasi langsung dengan saldo Shift.

Modul ini menginduk pada arsitektur API *backend* yang sama dengan B2B, di mana seluruh penjualan POS akan tercatat pada tabel universal `transactions`, namun tanpa memerlukan penerbitan entitas faktur `transaction_invoices`.

---

## 2. Requirements

- **Sinkronisasi Transaksi Offline-First**: Klien (Flutter) harus menyimpan seluruh data produk, pelanggan, pengaturan fleksibilitas, dan riwayat transaksi secara lokal. Transaksi yang terjadi saat *offline* masuk ke antrian sinkronisasi lokal dan dikirim secara berurutan ke *backend* API saat *online*.
- **Integrasi Hardware (Native)**: Dukungan langsung untuk mencetak struk (*receipt*) menggunakan Thermal Printer via Bluetooth/USB dan integrasi dengan *Barcode Scanner*.
- **Fleksibilitas Bisnis (Configurable di Level Outlet)**:
  - **Bypass Shift**: Opsi untuk membiarkan aplikasi berjalan tanpa harus memaksa kasir membuka Shift (saldo awal/akhir dinonaktifkan).
  - **Open Price / Override Harga**: Kasir dapat mengubah nominal harga barang di keranjang belanja dengan cepat.
  - **Custom Diskon**: Memungkinkan input diskon berupa persentase maupun potongan harga tunai langsung (*custom nominal*) pada level *item* maupun *grand total*.
  - **Negative Stock Allowance**: Opsi untuk mencegah atau membiarkan transaksi berlanjut meskipun stok barang di database sudah nol atau minus.
- **Manajemen Kas (Cash In / Out)**: Form ringkas bagi kasir untuk memasukkan uang di luar transaksi (contoh: tambahan uang kembalian) atau mengambil uang dari laci (contoh: untuk membayar parkir/bahan baku darurat).
- **Keamanan & Otorisasi**: Penggunaan PIN lokal (tanpa perlu hit API) untuk mengakses fitur kritis seperti Void/Cancel, ubah harga, atau Retur.

---

## 3. Core Features

1. **Dashboard Point of Sale**: Antarmuka katalog produk (*grid/list*), pencarian cepat, *barcode scanning*, dan keranjang belanja interaktif (*Cart*).
2. **Offline-First Transaction Queueing**: Mesin sinkronisasi yang secara aman menyimpan *payload* pesanan lokal dan melakukan *retry* otomatis jika gagal dikirim ke *server*.
3. **Manajemen Shift Kasir**: Proses pencatatan uang modal (*Cash Drawer*) saat buka toko, dan rekapitulasi penjualan saat tutup kasir (*Close Shift*).
4. **Manajemen Kas Harian**:
   - *Cash In*: Tambah saldo laci kasir secara manual.
   - *Cash Out*: Tarik saldo laci kasir beserta kolom keterangan.
5. **Operasional Kasir Fleksibel**:
   - *Hold & Resume Transaction*: Kasir dapat menyimpan pesanan (*Save Bill*) pelanggan yang menunda pembayaran, lalu melayani pelanggan berikutnya.
   - *Split Bill*: Dukungan pembayaran terpisah untuk satu transaksi.
   - Diskon & Pajak Otomatis (atau Manual).
6. **Thermal Printing**: Cetak struk belanja pelanggan dan laporan tutup *shift* via koneksi Bluetooth.

---

## 4. User Flow

1. **Inisialisasi & Login**:
   - Kasir membuka aplikasi Flutter. Aplikasi mengunduh pembaruan data master (Produk, Harga, Setting Fleksibilitas) dari *server* ke *local database*.
   - Kasir login (biasanya menggunakan PIN).
2. **Buka Shift (Opsional/Fleksibel)**:
   - Jika *setting* "Bypass Shift" dinonaktifkan, kasir diwajibkan menginput Saldo Awal Laci (*Starting Cash*).
3. **Proses Transaksi**:
   - Kasir memindai *barcode* atau memilih produk dari katalog.
   - Jika pelanggan menawar, kasir dapat melakukan *Override Harga* atau memberi Diskon Kustom (jika fitur ini diaktifkan di *settings*).
   - Kasir melakukan *Hold* jika pelanggan belum siap membayar.
   - Kasir beralih ke menu Pembayaran, memilih metode (Cash, QRIS, dll), dan menyelesaikan transaksi.
   - Aplikasi mencetak struk secara instan ke *Thermal Printer* dan menyimpan transaksi ke database SQLite lokal.
4. **Sinkronisasi Background**:
   - Secara asinkron, aplikasi Flutter mengecek koneksi internet dan mengirim *payload* transaksi ke *endpoint* API Laravel. Jika berhasil, transaksi di-*flag* sebagai *Synced*.
5. **Manajemen Kas Harian**:
   - Sewaktu-waktu, jika ada pengeluaran operasional (misal: beli galon air), kasir membuka menu **Kasir > Cash Out**, memasukkan nominal dan catatan. Saldo laci akan terpotong secara sistem.
6. **Tutup Shift**:
   - Di penghujung hari, kasir memilih "Tutup Shift", menghitung fisik uang tunai di laci, mencocokkannya dengan estimasi sistem, lalu mencetak Laporan Shift.

---

## 5. Architecture

Sistem mengimplementasikan **Clean Architecture** pada aplikasi klien Flutter, memisahkan logika antarmuka (*Presentation*), logika bisnis (*Domain*), dan pengelolaan sumber data (*Data*).

```mermaid
flowchart TD
    subgraph Klien Flutter [Sollu POS Client (Flutter)]
        UI[Presentation Layer - Riverpod / UI]
        Domain[Domain Layer - UseCases & Entities]
        
        subgraph Data Layer
            Repo[Repositories]
            LocalDB[(Drift / SQLite Local DB)]
            APIClient[Dio HTTP Client]
        end
        
        UI <--> Domain
        Domain <--> Repo
        Repo <--> LocalDB
        Repo <-->|Queue Sync saat Online| APIClient
    end

    subgraph Server [Sollu App (Laravel Backend)]
        API[Laravel REST API]
        Postgres[(PostgreSQL)]
        API <--> Postgres
    end

    APIClient <-->|REST over HTTPS| API
    UI -->|Bluetooth| Printer(Thermal Printer)
```

**Alur Offline-First**: Semua aksi pembacaan dan penulisan (*read/write*) yang dilakukan oleh kasir hanya mengenai `LocalDB` (Drift). Sebuah *worker/service* terpisah (*Queue Sync*) bertanggung bertanggung jawab membaca data lokal yang belum tersinkronisasi dan mengirimkannya melalui `APIClient` (Dio) ke *Server* di *background*.

---

## 6. Database Schema

### Skema Lokal (Flutter - Drift SQLite)
Tabel lokal di Flutter bertindak sebagai cermin dari tabel *backend*, namun memiliki kolom `sync_status` (enum: `pending`, `synced`, `failed`).
- `local_transactions`
- `local_transaction_items`
- `local_transaction_payments`
- `local_shifts`
- `local_cash_register_logs` (Untuk manajemen kas)

### Skema Server (Laravel - PostgreSQL)
Tabel ini bertindak sebagai tujuan akhir dari sinkronisasi POS.

1. **`transactions` (Tabel Induk)**
   - `id`: UUID, PK
   - `shift_id`: UUID, FK (Opsional, jika fitur Bypass Shift aktif)
   - `outlet_id`, `customer_id`: UUID
   - `channel`: enum (`dine_in`, `walk_in`, `take_away`, dll)
   - `total`, `total_paid`: decimal(15,4)
   - `status`: enum (Pada POS ritel, status biasanya langsung menuju `paid`)

2. **`shifts` (Manajemen Shift Kasir)**
   - `id`: UUID, PK
   - `outlet_id`, `user_id`: UUID
   - `starting_cash`: decimal (Modal laci awal)
   - `ending_cash_expected`: decimal (Ekspektasi sistem)
   - `ending_cash_actual`: decimal (Perhitungan aktual kasir)
   - `status`: enum (`open`, `closed`)
   - `opened_at`, `closed_at`: datetime

3. **`cash_register_logs` (Manajemen Kas / Cash In & Out)**
   - `id`: UUID, PK
   - `shift_id`: UUID, FK
   - `type`: enum (`cash_in`, `cash_out`)
   - `amount`: decimal(15,4)
   - `notes`: text
   - `recorded_at`: datetime

4. **`outlet_settings` (Tabel Konfigurasi Fleksibilitas)**
   - `bypass_shift_pos`: boolean (Opsi Kasir Tanpa Shift)
   - `allow_override_price_pos`: boolean
   - `allow_custom_discount_pos`: boolean
   - `allow_negative_stock_pos`: boolean

---

## 7. Tech Stack

- **Aplikasi Klien (Sollu POS Client)**: 
  - Framework: **Flutter (Dart 3)**.
  - State Management: **Riverpod**.
  - Local Database: **Drift (SQLite)**.
  - Networking: **Dio** (dengan *Interceptors* untuk manajemen *Token* dan penanganan gangguan koneksi).
  - Routing: **GoRouter**.
  - Hardware Integrations: Pustaka pihak ketiga seperti `print_bluetooth_thermal` dan perangkat *Barcode Scanner* berbasis USB/Bluetooth HID.
- **Server / Backend (Sollu App)**:
  - **Laravel 11** untuk penyediaan REST API *endpoint* sinkronisasi (`/api/v1/pos/sync`, dll).
  - Autentikasi berbasis *Laravel Sanctum* (mengeluarkan token API untuk setiap mesin POS).
  - **PostgreSQL** untuk penampung master data terpusat.
