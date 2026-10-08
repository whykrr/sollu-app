# PRD — Modul Transaksi & Penjualan - Channel POS App V1.3
## Core Fast Checkout, Hold-Resume, Delta Sync & Realtime Stream

## 1. Executive Summary & Bounded Context

Sub-modul **V1.3 Core Fast Checkout, Hold-Resume, Delta Sync & Realtime Stream** adalah mesin inti (*engine*) transaksi penjualan pada aplikasi kasir **Sollu POS Client**. Modul ini bertanggung jawab atas alur checkout ultra-cepat berlatensi rendah (< 50ms), kalkulasi keranjang belanja (diskon baris/dokumen, pajak, kembalian), penundaan tagihan (*Hold Bill*) dan pembukaan kembali (*Resume Bill*), pengiriman transaksi asinkron ke server Laravel (`POST /api/v1/pos/transactions`), pemulihan rekoneksi delta sync dengan mekanisme *handshake* realtime Reverb (`GET /api/v1/pos/sync?last_handshake_at=...`), serta penyaluran perubahan master produk & saldo stok inventori secara seketika (*realtime*) menggunakan **Laravel Reverb WebSocket** yang dipicu otomatis oleh **Laravel Eloquent Observer** dan diamankan oleh **Laravel Sanctum**.

### Kapabilitas Utama V1.3:
1. **Ultra-Fast Local Checkout (< 50ms)**: Transaksi penjualan disimpan seketika ke SQLite lokal (`local_transactions`), memicu pencetakan struk fisik dan membuka laci kas tanpa menunggu respons jaringan internet.
2. **Dedicated Transaction Submission (`POST /api/v1/pos/transactions`)**: Transaksi penjualan lokal dikirimkan secara mandiri melalui *Background Sync Worker* secara FIFO ke server tanpa mencampuradukkan payload master data.
3. **Idempotency & Zero Double-Deduction**: Pencegahan pemotongan stok berulang dan duplikasi transaksi via pemeriksaan `offline_id` UUIDv4 di server backend.
4. **Hold & Resume Transactions (*Held Bills*)**: Kasir dapat memarkir transaksi pelanggan yang menunda pembayaran ke tabel `local_held_transactions` dan melanjutkannya kembali kapan saja.
5. **Reconnection Handshake & Delta Sync Engine (`GET /api/v1/pos/sync`)**: Saat internet kembali online, terminal POS mengeksekusi endpoint sync sebagai *trigger handshake* data realtime Reverb antara backend cloud dengan POS client dengan mengirimkan `last_handshake_at`. Backend mengidentifikasi gap data dan memicu pembaruan data yang tertinggal (baik via respons delta langsung maupun via WebSocket channel Reverb).
6. **Realtime Broadcast via Laravel Reverb & Eloquent Observer**: Menggunakan **Laravel Observer** (`ProductObserver`, `InventoryBalanceObserver`) sebagai pemicu (*trigger*) otomatis saat terjadi mutasi data di server, menyiarkan event pembaruan harga, penonaktifan produk, dan mutasi saldo stok ke seluruh terminal kasir aktif di outlet via WebSocket `private-outlet.{outlet_id}.pos`.
7. **Strict Background Reconnection Pipeline (Non-Blocking)**: Saat perangkat beralih dari kondisi *offline* ke *online*, sistem mengeksekusi urutan sinkronisasi ketat di latar belakang tanpa mengganggu atau membekukan aktivitas kasir yang sedang bertransaksi:
   - **Langkah 1**: Eksekusi endpoint sync (`GET /api/v1/pos/sync`) pertama kali untuk handshake Reverb dan sinkronisasi delta master data / stok.
   - **Langkah 2**: Dilanjutkan eksekusi sinkronisasi antrean transaksi lokal pending (`POST /api/v1/pos/transactions`) secara FIFO.

---

## 2. Architecture & Domain Flow

```
Flutter Client (sollu_pos_client)                     Laravel 12 Backend
┌─────────────────────────────────┐                   
│ CartNotifier & Checkout Flow    │                   
│ (Instant Save to Drift SQLite)  │                   
└────────────────┬────────────────┘                   
                 │ (UUIDv4 Generated)                 
                 ▼                                    
┌─────────────────────────────────┐                   
│ Drift DB: local_transactions    │                   
│ Status: pending                 │                   
└────────────────┬────────────────┘                   
                 │                                    
                 │ [Device Online Reconnection Event]
                 ▼                                    
┌────────────────────────────────────────────────────────┐
│ Background Sync Worker (Non-Blocking Isolated Thread)  │
│                                                        │
│ 1. STEP 1 (FIRST): Handshake Trigger & Delta Sync      │
│    GET /api/v1/pos/sync?last_handshake_at=<ISO>        │
│                                                        │
│ 2. STEP 2 (SECOND): Push Pending Transactions (FIFO)   │
│    POST /api/v1/pos/transactions                       │
└──────────────┬───────────────────────────┬─────────────┘
               │ (Step 1 Request)          │ (Step 2 Request)
               ▼                           ▼
┌───────────────────────────────┐ ┌───────────────────────────────┐
│ PosSyncController (Handshake) │ │ TransactionController (FIFO)  │
│ GET /api/v1/pos/sync          │ │ POST /api/v1/pos/transactions │
└──────────────┬────────────────┘ └───────────────▲───────────────┘
               │ (Handshake OK + Delta Data)      │
               ▼                                  │
┌───────────────────────────────┐                 │
│ Drift DB: Update Catalog/Stock│                 │
└───────────────────────────────┘                 │
                                                  │
┌───────────────────────────────┐ ┌───────────────┴───────────────┐
│ PosReverbClient (WebSocket)   │ │ Laravel Reverb Gateway        │
│ private-outlet.{outletId}.pos │◄│ (Sanctum Authenticated)       │
└───────────────────────────────┘ └───────────────▲───────────────┘
                                                  │ (Broadcast Event)
                                  ┌───────────────┴───────────────┐
                                  │ Laravel Eloquent Observers    │
                                  │ - ProductObserver             │
                                  │ - InventoryBalanceObserver    │
                                  └───────────────────────────────┘
```

> **Prinsip Non-Blocking & Urutan Eksekusi**:
> 1. **Zero UI Interruption**: Seluruh proses sinkronisasi rekoneksi dieksekusi secara asinkron di *background worker*. Kasir dapat terus menambah item ke keranjang belanja, memproses checkout tunai baru, atau mencetak struk tanpa jeda/freeze UI sama sekali.
> 2. **Urutan Eksekusi Wajib (Handshake Sync -> Transaction Push)**: Saat internet kembali pulih, sistem POS **wajib mengeksekusi endpoint sync terlebih dahulu** untuk melakukan handshake realtime Reverb dan menyerap katalog/stok termutakhir. Setelah endpoint sync berhasil, sistem baru melanjutkan pengiriman antrean transaksi lokal secara FIFO.

---

## 3. Core Features & User Activity Use Cases

### 3.1. Fitur Utama

| Fitur | Deskripsi |
| :--- | :--- |
| **Keranjang Belanja Reaktif (*Cart Engine*)** | Kalkulasi subtotal, diskon per item, diskon global keranjang, pajak PPN 11%, dan biaya layanan (*service charge*). |
| **Modal Pembayaran Kilat (*Quick Pay*)** | Tombol pecahan uang pas (*Exact Cash*, 50rb, 100rb), kalkulator kembalian otomatis, dan metode multi-bayar. |
| **Simpan Tagihan (*Hold Bill*)** | Menyimpan isi keranjang aktif ke antrean pending tanpa memotong stok, memberi nama catatan (contoh: "Meja 5"). |
| **Buka Tagihan (*Resume Bill*)** | Drawer daftar held bills dengan jam simpan, tombol muat ulang ke keranjang (*resume*), dan tombol hapus. |
| **Reconnection Handshake & Delta Sync (Step 1)** | Endpoint `GET /api/v1/pos/sync` dieksekusi **pertama kali** saat kembali online, mengirimkan `last_handshake_at` untuk inisialisasi handshake Reverb dan delta download perubahan master data/stok. |
| **Sequential Transaction Push (Step 2)** | Pengiriman transaksi lokal ke endpoint `POST /api/v1/pos/transactions` berstatus FIFO dieksekusi **setelah** delta sync selesai, berjalan di background tanpa memblokir kasir. |
| **Live Reverb Event Stream via Eloquent Observers** | Perubahan harga, penonaktifan produk, dan mutasi stok di backend dipantau oleh Laravel Observer (`ProductObserver`, `InventoryBalanceObserver`) yang otomatis menembakkan event Reverb seketika (< 100ms) ke channel WebSocket POS. |

### 3.2. Skenario Aktivitas Pengguna (Use Cases)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ Skenario Pengguna POS V1.3                                                                             │
├──────────────────────────────┬──────────────────────────────┬──────────────────────────────────────────┤
│ Use Case                     │ Kondisi & Perangkat          │ Respon & Output Sistem                   │
├──────────────────────────────┼──────────────────────────────┼──────────────────────────────────────────┤
│ 1. Checkout Cepat Kasir      │ Internet: Terputus (Offline) │ Transaksi tersimpan lokal < 50ms, struk  │
│    (Offline Sale Checkout)   │ Pembayaran: Tunai            │ tercetak, status lokal: `pending_sync`.  │
├──────────────────────────────┼──────────────────────────────┼──────────────────────────────────────────┤
│ 2. Tahan Tagihan (Hold Bill) │ Pelanggan lupa bawa dompet   │ Kasir klik "Hold", input "Meja 12".      │
│                              │ Kasir harus layani antrean   │ Keranjang bersih, siap melayani antrean. │
├──────────────────────────────┼──────────────────────────────┼──────────────────────────────────────────┤
│ 3. Buka Tagihan (Resume Bill)│ Pelanggan Meja 12 siap bayar │ Kasir buka drawer Held Bills, klik       │
│                              │ Antrean sebelumnya selesai   │ Resume. Item kembali ke keranjang kasir. │
├──────────────────────────────┼──────────────────────────────┼──────────────────────────────────────────┤
│ 4. Pemulihan Pasca Offline   │ Internet kembali terhubung   │ Background Worker berjalan senyap:       │
│    (Handshake Sync -> Push)  │ Ada 20 transaksi pending     │ 1. Urutan 1: Hit GET /pos/sync           │
│                              │ Kasir sedang sibuk input     │    (kirim last_handshake_at & delta).    │
│                              │ keranjang transaksi baru     │ 2. Urutan 2: Push 20 transaksi pending   │
│                              │                              │    secara FIFO ke /pos/transactions.     │
│                              │                              │ 3. UI Kasir 100% responsif tanpa jeda.   │
├──────────────────────────────┼──────────────────────────────┼──────────────────────────────────────────┤
│ 5. Update Harga & Stok Live  │ Internet: Online             │ Admin ubah harga kopi di Web Portal.     │
│    (Reverb via Observer)     │ Aplikasi Kasir Standby       │ ProductObserver trigger event Reverb,    │
│                              │                              │ harga di layar kasir berubah seketika.   │
└──────────────────────────────┴──────────────────────────────┴──────────────────────────────────────────┘
```

---

## 4. User Flow & Sequence Diagrams

### 4.1. Alur Transaksi Kasir Offline & Background Push

```mermaid
sequenceDiagram
    actor Cashier as Kasir
    participant UI as POS Cart & Checkout
    participant LocalDB as Drift SQLite
    participant Printer as Thermal Printer
    participant Worker as Background Sync Worker
    participant API as Laravel Backend (/api/v1/pos/transactions)

    Cashier->>UI: Tambah Item -> Klik "Bayar" -> Pilih "Tunai Rp 100.000"
    UI->>LocalDB: INSERT local_transactions (id: UUIDv4, sync_status: pending)
    UI->>LocalDB: INSERT local_transaction_items & local_transaction_payments
    par
        UI->>Printer: Cetak Struk Belanja ESC/POS
        Printer-->>Cashier: Struk Kertas Keluar
    and
        UI->>Worker: Beritahu Transaksi Baru
    end
    UI-->>Cashier: Tampilkan Layar Sukses & Kembalian Uang (< 50ms)

    opt Ada Jaringan Internet
        Worker->>LocalDB: Query transaksi pending (Urutan created_at ASC)
        LocalDB-->>Worker: Data Transaksi Payload
        Worker->>API: POST /api/v1/pos/transactions (StorePosTransactionRequest)
        API->>API: Cek Idempotensi, Simpan ke Universal DB, Potong Stok FIFO
        API-->>Worker: 200 OK (Sync Success)
        Worker->>LocalDB: UPDATE local_transactions SET sync_status = 'synced'
    end
```

### 4.2. Alur Reconnection Sequence & Realtime Reverb Stream (via Observer)

Urutan eksekusi saat koneksi internet pulih (*reconnected*):
1. **Urutan 1 (Wajib Pertama)**: Eksekusi endpoint sync (`GET /api/v1/pos/sync`) membawa `last_handshake_at` untuk handshake realtime Reverb & pull delta.
2. **Urutan 2**: Dilanjutkan eksekusi pengiriman antrean transaksi lokal pending (`POST /api/v1/pos/transactions`) secara FIFO.
3. Seluruh proses berjalan di latar belakang (*background worker*) tanpa memblokir UI kasir yang sedang aktif bertransaksi.

```mermaid
sequenceDiagram
    actor Cashier as Kasir (UI Thread)
    participant Worker as Background Sync Worker
    participant LocalDB as Drift SQLite
    participant API as Laravel Backend (/api/v1/pos)
    participant Observer as Laravel Eloquent Observer
    participant Reverb as Laravel Reverb WebSocket

    Note over Cashier,Worker: Kondisi: Device baru saja kembali ONLINE
    Note over Cashier: Kasir tetap melayani antrean & input keranjang (Non-Blocking)

    rect rgb(240, 248, 255)
    Note over Worker,API: TAHAP 1: Handshake Reverb & Delta Sync (Pertama Kali)
    Worker->>API: GET /api/v1/pos/sync?last_handshake_at=2026-10-01T10:00:00Z
    Note over API: Backend evaluasi last_handshake_at,<br/>trigger data gap & set status handshake
    API-->>Worker: 200 OK { handshake: ack, updated_products, updated_stocks }
    Worker->>LocalDB: Batch UPDATE local_product_cache & stock
    Worker->>API: POST /api/broadcasting/auth (Sanctum Auth)
    API-->>Worker: Broadcast Channel Signature OK
    Worker->>Reverb: Re-subscribe 'private-outlet.{outlet_id}.pos'
    end

    rect rgb(255, 250, 240)
    Note over Worker,API: TAHAP 2: Eksekusi Sync Transaksi Pending (FIFO Push)
    Worker->>LocalDB: Query local_transactions WHERE sync_status = 'pending' (ASC)
    LocalDB-->>Worker: List 20 Transaksi Offline
    loop Tiap Transaksi (FIFO)
        Worker->>API: POST /api/v1/pos/transactions (StorePosTransactionRequest)
        API->>API: Idempotency Check & Potong Stok FIFO
        API-->>Worker: 200 OK (Sync Success)
        Worker->>LocalDB: UPDATE local_transactions SET sync_status = 'synced'
    end
    end

    rect rgb(240, 255, 240)
    Note over Observer,Cashier: SKENARIO REALTIME: Admin Ubah Data via Web Portal
    Observer->>Observer: Model Event: Product::updated / InventoryBalance::updated
    Observer->>Reverb: Dispatch PosProductUpdatedEvent (ShouldBroadcastNow)
    Reverb->>Worker: WebSocket Event 'PosProductUpdatedEvent' { product_id, new_price }
    Worker->>LocalDB: UPDATE local_product_cache SET price = new_price
    Worker-->>Cashier: Riverpod Notifier update UI Keranjang / Katalog (< 100ms)
    end
```

---

## 5. Technical Architecture & File Structure

### 5.1. File Structure Klien Flutter (`sollu_pos_client`)

```
lib/features/pos/
├── presentation/
│   ├── controllers/
│   │   ├── cart_controller.dart                 # Riverpod Notifier kalkulasi keranjang
│   │   ├── checkout_controller.dart             # Penanganan proses pembayaran
│   │   └── held_bills_controller.dart           # Manajemen Hold & Resume bill
│   ├── screens/
│   │   ├── quick_pay_modal.dart                 # Pop-up pembayaran cepat & kembalian
│   │   └── held_bills_drawer.dart               # Drawer daftar tagihan yang ditahan
│   └── widgets/
│       ├── cart_item_tile.dart                  # Baris item keranjang (qty, diskon, catatan)
│       └── cart_summary_bar.dart                # Subtotal, diskon, pajak, grand total
├── domain/
│   ├── entities/
│   │   ├── cart_item.dart
│   │   └── pos_sale_transaction.dart
│   └── usecases/
│       ├── process_sale_usecase.dart
│       ├── hold_bill_usecase.dart
│       └── resume_bill_usecase.dart
└── data/
    ├── database/
    │   ├── tables/
    │   │   ├── local_transactions.dart
    │   │   ├── local_transaction_items.dart
    │   │   ├── local_transaction_payments.dart
    │   │   └── local_held_transactions.dart
    │   └── daos/
    │       ├── transaction_dao.dart
    │       └── held_bills_dao.dart
    ├── sync/
    │   ├── background_sync_worker.dart          # Orchestrator sinkronisasi non-blocking (Fase 1: Sync Handshake, Fase 2: Transaction Push FIFO)
    │   └── sync_delta_service.dart              # Client API pemanggil /pos/sync dengan last_handshake_at
    └── realtime/
        └── pos_reverb_client.dart               # Listener WebSocket Reverb channel private-outlet.{outletId}.pos
```

### 5.2. File Structure Backend Laravel 12 (`sollu-app`)

```
app/
├── Http/
│   ├── Controllers/API/POS/
│   │   ├── TransactionController.php            # POST /api/v1/pos/transactions
│   │   └── PosSyncController.php                # GET /api/v1/pos/sync (Handshake trigger & delta sync)
│   └── Requests/API/POS/
│       ├── StorePosTransactionRequest.php       # Validasi lengkap payload transaksi
│       └── PosSyncHandshakeRequest.php          # Validasi parameter last_handshake_at & outlet_id
├── Observers/POS/
│   ├── ProductObserver.php                      # Trigger PosProductUpdatedEvent saat harga/status produk berubah
│   └── InventoryBalanceObserver.php             # Trigger PosStockBalanceUpdatedEvent saat mutasi stok terjadi
├── Events/POS/
│   ├── PosProductUpdatedEvent.php               # ShouldBroadcastNow ke WebSocket Reverb
│   └── PosStockBalanceUpdatedEvent.php          # ShouldBroadcastNow ke WebSocket Reverb
└── Services/App/Transaction/
    └── TransactionService.php                   # Pemotongan stok FIFO & idempotensi
```

### 5.3. Public API Contracts

#### 5.3.1. Submit Transaksi POS (`POST /api/v1/pos/transactions`)
- *Endpoint*: `POST /api/v1/pos/transactions`
- *Request Body*:
  ```json
  {
    "offline_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "transaction_number": "POS/20261001/0001",
    "shift_id": null,
    "transaction_date": "2026-10-01T12:00:00+07:00",
    "subtotal": 50000.0,
    "discount_amount": 5000.0,
    "tax_amount": 4950.0,
    "total": 49950.0,
    "payment_status": "paid",
    "status": "completed",
    "items": [
      {
        "product_id": "7b0deb4c-1b7d-4aad-9bee-1b0d7b3dcb2b",
        "qty": 2.0,
        "price": 25000.0,
        "subtotal": 50000.0
      }
    ],
    "payments": [
      {
        "payment_method": "cash",
        "amount": 50000.0,
        "change_amount": 50.0
      }
    ]
  }
  ```

#### 5.3.2. Reconnection Handshake & Delta Sync (`GET /api/v1/pos/sync`)
- *Tujuan*: Berfungsi sebagai **trigger handshake data realtime Reverb** antara backend cloud Laravel dengan terminal POS client, sekaligus mengunduh delta perubahan katalog dan stok yang terjadi selama perangkat offline.
- *Aturan Eksekusi*: **Wajib dieksekusi pertama kali** saat perangkat mendeteksi jaringan internet pulih, sebelum mengeksekusi antrean push transaksi lokal.
- *Endpoint*: `GET /api/v1/pos/sync?last_handshake_at=2026-10-01T10:00:00Z&outlet_id=9b1deb4c-2b7d-4aad-9bee-1b0d7b3dcb1a`
- *Query Parameters*:
  - `last_handshake_at` (string ISO 8601, required): Waktu rekaman handshake/sinkronisasi terakhir yang tersimpan di perangkat POS. Backend menggunakannya untuk mengevaluasi gap data yang perlu diperbarui dan memvalidasi state streaming Reverb.
  - `outlet_id` (UUIDv4, required): ID outlet terminal aktif untuk otentikasi channel WebSocket outlet terkait.
- *Response (200 OK)*:
  ```json
  {
    "success": true,
    "message": "Handshake acknowledged & delta synchronized successfully",
    "data": {
      "handshake_status": "acknowledged",
      "handshake_at": "2026-10-01T12:05:00+07:00",
      "synced_at": "2026-10-01T12:05:00+07:00",
      "reverb_channel": "private-outlet.9b1deb4c-2b7d-4aad-9bee-1b0d7b3dcb1a.pos",
      "updated_products": [
        {
          "id": "7b0deb4c-1b7d-4aad-9bee-1b0d7b3dcb2b",
          "name": "Kopi Susu Gula Aren",
          "price": 28000.0,
          "stock": 38.0,
          "is_active": true
        }
      ],
      "deleted_product_ids": []
    }
  }
  ```

---

## 6. Database Schema & Data Integrity

### 6.1. Schema PostgreSQL (Server)
Data bermuara ke tabel universal `transactions`, `transaction_items`, `transaction_payments`, dan `inventory_movements`.

### 6.2. Schema Drift SQLite (Client)

```dart
class LocalTransactions extends Table {
  TextColumn get id => text()(); // UUIDv4
  TextColumn get transactionNumber => text()();
  TextColumn get shiftId => text().nullable()();
  RealColumn get subtotal => real()();
  RealColumn get discountAmount => real().withDefault(const Constant(0.0))();
  RealColumn get taxAmount => real().withDefault(const Constant(0.0))();
  RealColumn get total => real()();
  TextColumn get syncStatus => text()(); // pending, syncing, synced, failed
  DateTimeColumn get createdAt => dateTime()();

  @override
  Set<Column> get primaryKey => {id};
}

class LocalHeldTransactions extends Table {
  TextColumn get id => text()();
  TextColumn get customerNote => text().nullable()();
  TextColumn get cartPayloadJson => text()(); // Serialized items
  DateTimeColumn get heldAt => dateTime()();

  @override
  Set<Column> get primaryKey => {id};
}
```

---

## 7. Single Source of Truth: Enums

```php
namespace App\Enums;

enum TransactionStatus: string {
    case DRAFT = 'draft';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

enum SyncStatusEnum: string {
    case PENDING = 'pending';
    case SYNCING = 'syncing';
    case SYNCED = 'synced';
    case FAILED = 'failed';
}
```

---

## 8. Testing & Quality Assurance

- `test_offline_checkout_persists_instantly_in_drift()`
- `test_reconnection_pipeline_executes_sync_handshake_before_transaction_push()`: Memastikan endpoint sync dieksekusi pertama kali sebelum transaksi pending dikirim saat online.
- `test_background_sync_is_non_blocking_to_cashier_cart_flow()`: Memastikan thread worker tidak memblokir render UI atau aktivitas kasir.
- `test_sync_endpoint_acknowledges_last_handshake_at_and_returns_gap_delta()`: Memastikan parameter `last_handshake_at` dievaluasi backend untuk trigger handshake dan delta data.
- `test_background_sync_pushes_pending_transactions_fifo()`
- `test_idempotency_prevents_duplicate_transactions_with_same_offline_id()`
- `test_eloquent_product_observer_dispatches_reverb_broadcast_event()`: Memastikan mutasi model Product memicu event Reverb broadcast.
- `test_eloquent_inventory_balance_observer_dispatches_reverb_broadcast_event()`: Memastikan mutasi saldo stok memicu event Reverb broadcast.
- `test_reverb_event_updates_local_catalog_price_in_realtime()`

---

## 9. Implementation Plan & Definition of Done

### Deliverables:
1. **Backend**:
   - Controller `/api/v1/pos/sync` dengan validasi `last_handshake_at` & otentikasi handshake Reverb.
   - Controller `/api/v1/pos/transactions` dengan FIFO processor & idempotency check.
   - Eloquent Observers (`ProductObserver`, `InventoryBalanceObserver`) untuk auto-trigger event Reverb (`PosProductUpdatedEvent`, `PosStockBalanceUpdatedEvent`).
   - Service Inventory Deduction & FIFO stock costing.
2. **Client**:
   - Background Sync Worker dengan 2-phase reconnection pipeline (Urutan 1: Handshake Delta Sync -> Urutan 2: Transaction Queue Push FIFO) berjalan senyap di background.
   - Cart state & checkout calculation, Quick Pay Modal, Hold & Resume drawer.
   - Reverb WebSocket client listener terintegrasi ke SQLite cache dan Riverpod state notifiers.

### Definition of Done (DoD):
- Saat perangkat offline kembali online, sistem secara otomatis mengeksekusi endpoint sync terlebih dahulu, lalu dilanjutkan pengiriman antrean transaksi lokal.
- Seluruh eksekusi sync pasca offline berjalan di latar belakang (background) tanpa mengganggu aktivitas kasir atau menyebabkan jeda input pada UI keranjang.
- Endpoint sync sukses menerima `last_handshake_at`, memvalidasi handshake Reverb, dan mengembalikan delta data terbaru.
- Perubahan harga atau saldo stok di backend yang dipicu oleh Web Portal ter-trigger otomatis oleh Laravel Observer dan tersiar ke seluruh terminal POS aktif dalam waktu < 200ms.
- Transaksi offline dapat dieksekusi 100 kali berturut-turut tanpa jeda/hang dan seluruhnya tersinkronisasi tanpa duplikasi data atau selisih stok saat kembali online.
