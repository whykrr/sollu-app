# Sollu App - Point of Sale (SaaS)

Sollu POS adalah aplikasi Point of Sale berbasis SaaS yang dirancang khusus untuk bisnis dengan model multi-outlet. Setiap merchant dapat mengelola satu atau lebih outlet dengan sistem langganan (subscription) yang fleksibel.

## 🚀 Fitur Utama
- **Multi-Outlet Management:** Kelola beberapa cabang toko dalam satu akun merchant.
- **Sistem Langganan (SaaS):** Fleksibilitas paket berlangganan untuk merchant.
- **Role & Permission:** Hak akses sistem yang komprehensif untuk pemilik, manajer, dan kasir (menggunakan Spatie).
- **Payment Gateway Integration:** Mendukung pembayaran online terintegrasi (Midtrans).
- **Ekspor Dokumen:** Cetak struk dan laporan dalam format PDF.
- **Dashboard & Analitik:** Visualisasi data interaktif menggunakan Chart.js.

## 🛠️ Teknologi yang Digunakan

**Backend:**
- [Laravel 11](https://laravel.com/) (PHP ^8.3)
- [Spatie Permission](https://spatie.be/docs/laravel-permission/v6/introduction) (Manajemen role & permission)
- [Midtrans PHP](https://midtrans.com/) (Payment gateway)
- [DomPDF](https://github.com/barryvdh/laravel-dompdf) (Pembuatan dokumen PDF)
- Redis / Predis (Caching & Queue)

**Frontend:**
- [Vue 3](https://vuejs.org/) (Composition API)
- [Inertia.js](https://inertiajs.com/) (Penghubung backend dan frontend)
- [Tailwind CSS v4](https://tailwindcss.com/) (Styling framework)
- [Pinia](https://pinia.vuejs.org/) (State management)
- [Ziggy](https://github.com/tighten/ziggy) (Penggunaan route Laravel di Vue)
- **Modul Tambahan:** FontAwesome, Chart.js, Swiper, Quill Editor, Vue Draggable.

## 🏗️ High-Level Architecture

Aplikasi ini menggunakan arsitektur **Monolitik Modern** (Monolith with SPA Frontend) dengan Inertia.js sebagai penghubung antara frontend dan backend.

```mermaid
flowchart TB
    User((Pengguna))

    subgraph Client ["Client Browser (Frontend)"]
        direction TB
        Vue["Vue 3 (SPA)"]
        Pinia[("Pinia (State Management)")]
        Vue <--> Pinia
    end

    subgraph Bridge ["Penghubung"]
        Inertia["Inertia.js Protocol"]
    end

    subgraph BackendServer ["Server (Backend)"]
        direction TB
        Laravel["Laravel 11"]
        Spatie{"Spatie Permission"}
        DomPDF["DomPDF (Report)"]
        Laravel <--> Spatie
        Laravel <--> DomPDF
    end

    subgraph DatabaseLayer ["Database & Cache"]
        direction LR
        DB[("MySQL / PostgreSQL")]
        Redis[("Redis")]
    end

    subgraph External ["Layanan Eksternal"]
        Midtrans["Midtrans Gateway"]
    end

    %% Relasi
    User -->|Interaksi UI| Client
    Vue <-->|AJAX / XHR| Inertia
    Inertia <-->|Controller & Routes| Laravel
    Laravel <-->|Eloquent ORM| DB
    Laravel <-->|Cache & Job Queues| Redis
    Laravel <-->|Payment API| Midtrans

    %% Styling
    classDef frontend fill:#42b883,stroke:#333,stroke-width:1px,color:#fff;
    classDef backend fill:#ff2d20,stroke:#333,stroke-width:1px,color:#fff;
    classDef bridge fill:#9553e9,stroke:#333,stroke-width:1px,color:#fff;
    classDef db fill:#00758f,stroke:#333,stroke-width:1px,color:#fff;
    classDef external fill:#333333,stroke:#333,stroke-width:1px,color:#fff;

    class Vue,Pinia frontend;
    class Laravel,Spatie,DomPDF backend;
    class Inertia bridge;
    class DB,Redis db;
    class Midtrans external;
```

1. **Client Layer (Vue 3 SPA):**
   - Berjalan di browser pengguna, menangani interaktivitas UI secara langsung.
   - Menggunakan **Pinia** untuk local state management.
   - Meminta data atau navigasi halaman melalui request XHR/Fetch ke backend (via Inertia).
2. **Bridge Layer (Inertia.js):**
   - Menghilangkan kebutuhan untuk membuat REST/GraphQL API terpisah.
   - Mencegah *full page reload* (SPA experience) dengan me-render komponen Vue berdasarkan *props* data yang dikirim oleh controller Laravel.
3. **Backend & Business Logic Layer (Laravel 11):**
   - Menangani proses inti bisnis (transaksi POS, manajemen inventaris, langganan).
   - Validasi input, autentikasi sesi pengguna, dan otorisasi dengan **Spatie Permission**.
   - Integrasi eksternal seperti **Midtrans** untuk pembayaran dan pembuatan PDF (DomPDF).
4. **Data Layer (Database & Cache):**
   - **RDBMS (MySQL/PostgreSQL):** Menyimpan data persisten seperti user, merchant, produk, dan transaksi.
   - **Redis (Cache & Queue):** Digunakan untuk menyimpan cache sementara dan menangani background jobs asinkron, seperti pengiriman email notifikasi atau pembuatan laporan dalam skala besar.

### 🔄 Internal Service Communication

Backend aplikasi ini mengadopsi pola arsitektur **Pragmatis (Mixed Architecture)**. Untuk menghindari *over-engineering*, pola yang digunakan disesuaikan dengan tingkat kerumitan suatu fitur:

```mermaid
flowchart TD
    Req([HTTP Request dari Inertia]) --> Router[Routes / Middleware]
    
    subgraph App ["Application Layer (Laravel)"]
        direction TB
        Controller[Controllers]
        FormRequest[Form Requests]
        Service[Services / Engine]
        JobEvent[Notifications / Jobs]
    end
    
    subgraph Storage ["Data & Background Processing"]
        direction TB
        Model[Models / Eloquent]
        DB[(Database MySQL)]
        Redis[(Redis Queue)]
        Worker[Queue Workers]
    end
    
    %% Relasi Alur Kerja
    Router -->|Cek Otorisasi Spatie| Controller
    
    Controller -->|Validasi Input| FormRequest
    FormRequest -.-> Controller
    
    Controller -->|A. Fitur Kompleks| Service
    Controller -->|B. Fitur Sederhana| Model
    
    Service -->|Akses Data Terpusat| Model
    
    Controller -.->|Trigger Notifikasi| JobEvent
    Service -.->|Trigger Background Task| JobEvent
    
    Model <--> DB
    JobEvent -->|Push ke Queue| Redis
    Redis --> Worker
    Worker -->|Eksekusi Asinkron| Model
    
    %% Styling
    classDef route fill:#f87171,stroke:#333,stroke-width:1px,color:#fff;
    classDef controller fill:#3b82f6,stroke:#333,stroke-width:1px,color:#fff;
    classDef service fill:#10b981,stroke:#333,stroke-width:1px,color:#fff;
    classDef model fill:#f59e0b,stroke:#333,stroke-width:1px,color:#fff;
    classDef job fill:#8b5cf6,stroke:#333,stroke-width:1px,color:#fff;

    class Router route;
    class Controller,FormRequest controller;
    class Service service;
    class Model model;
    class JobEvent,Worker job;
```

**Penjelasan Alur Kerja:**
- **Routes & Middleware:** Pintu masuk request yang bertugas memverifikasi autentikasi dan peran menggunakan Spatie Permission.
- **FormRequests:** Validasi input dipisahkan ke dalam FormRequest khusus (misal: `StoreUserRequest`) agar *controller* bersih dari aturan validasi.
- **Controllers & Models (CRUD Sederhana):** Untuk fitur CRUD dasar (seperti manajemen Karyawan/Master Data), Controller langsung berinteraksi dengan **Eloquent Models** yang dibungkus menggunakan `DB::transaction()`.
- **Services (Logika Kompleks):** Untuk fitur yang memuat aturan bisnis berbelit atau pemanggilan API pihak ketiga (seperti `BillingEngine`, `MidtransService`), Controller akan mendelegasikan pemrosesan ke *Service layer*.
- **Notifications & Jobs:** Proses pengiriman email (seperti `NewEmployee` notification), pembuatan laporan PDF massal, atau perhitungan langganan dijalankan di *background* lewat antrean (Redis Queue) untuk menjaga respon aplikasi tetap cepat.

### 📦 Struktur Layanan Utama (Service Layer)

Aplikasi ini menggunakan pola pelayanan (*Service Pattern*) untuk memusatkan *Business Logic*. Berikut adalah **Arsitektur Besar** (Global) yang memetakan relasi antar-modul secara makro, di mana fokus utamanya hanya pada `app/Services`:

```mermaid
flowchart TD
    %% Global Service Architecture
    subgraph ServiceLayer ["Service Layer (app/Services)"]
        direction TB
        DashSvc[Dashboard & Billing Module]
        InvSvc[Inventory Module]
        OutSvc[Outlet Module]
        MstSvc[Master Data Module]
        TransSvc[Transaction Module]
        PromoSvc[Promo & Bundle Module]
        RepSvc[Reporting & Analytics Module]
        CrmSvc[CRM & Support Module]
    end
    
    OutSvc -.->|Trigger Kalkulasi Prorate| DashSvc
    MstSvc -.->|Validasi Material/Resep| InvSvc
    TransSvc -.->|Potong Stok via Event| InvSvc
    PromoSvc -.->|Hitung Diskon Transaksi| TransSvc
    TransSvc -.->|Record Penjualan| RepSvc
    
    classDef module fill:#3b82f6,stroke:#1e3a8a,stroke-width:1px,color:#fff;
    class DashSvc,InvSvc,OutSvc,MstSvc,TransSvc,PromoSvc,RepSvc,CrmSvc module;
```

---

#### 1. Dashboard & Billing Module
Mengelola logika langganan, pembuatan tagihan, dan integrasi Midtrans.

```mermaid
flowchart TD
    subgraph BillingModule ["💸 Dashboard & Billing Module"]
        direction TB
        SubSvc[SubscriptionService]
        BE[BillingEngine]
        MS[MidtransService]
        SubInv[SubscriptionInvoiceService]
        SubPay[SubscriptionPaymentService]
        
        SubSvc -->|Generate Prorate/Recurring| BE
        SubPay -->|Charge Payment| MS
        BE -->|Create Invoice| SubInv
    end
    classDef svc fill:#10b981,stroke:#047857,stroke-width:1px,color:#fff;
    class SubSvc,BE,MS,SubInv,SubPay svc;
```

#### 2. Inventory Module
Menangani penyesuaian stok, mutasi gudang/outlet, dan Material Mentah.

```mermaid
flowchart TD
    subgraph InventoryModule ["📦 Inventory Module"]
        direction TB
        STransfer[StockTransferService]
        SOpname[StockOpnameService]
        SAdjust[StockAdjustmentService]
        SFreeze[StockFreezeService]
        POSvc[PurchaseOrderService]
        RMSvc[RawMaterialService]
        
        STransfer -->|Update Mutasi| SAdjust
        SOpname -->|Update Fisik| SAdjust
    end
    classDef svc fill:#10b981,stroke:#047857,stroke-width:1px,color:#fff;
    class STransfer,SOpname,SAdjust,SFreeze,POSvc,RMSvc svc;
```

#### 3. Outlet Management
Mengatur pembuatan outlet baru, jam operasional, dan pengelolaan perangkat/kasir.

```mermaid
flowchart TD
    subgraph OutletModule ["🏪 Outlet Management"]
        direction TB
        OutCreate[CreateOutletService]
        OutUpd[UpdateOutletService]
        OutStatus[ManageOutletStatusService]
        OutSet[ManageOutletSettingService]
        OutDev[ManageOutletDeviceService]
        OutHour[ManageOutletOperationalHourService]
    end
    classDef svc fill:#10b981,stroke:#047857,stroke-width:1px,color:#fff;
    class OutCreate,OutUpd,OutStatus,OutSet,OutDev,OutHour svc;
```

#### 4. Master Data
Menangani komponen data esensial operasional bisnis (*Master Data Management*).

```mermaid
flowchart TD
    subgraph MasterModule ["🏷️ Master Data"]
        direction TB
        CatSvc[CategoryService]
        ProdSvc[ProductService]
        ModSvc[ModifierService]
        RecSvc[RecipeService]
        MstInv[InventoryService]
        
        ProdSvc -->|Komposisi Bahan| RecSvc
    end
    classDef svc fill:#10b981,stroke:#047857,stroke-width:1px,color:#fff;
    class CatSvc,ProdSvc,ModSvc,RecSvc,MstInv svc;
```

#### 5. Transaction Module (POS)
Mengelola inti proses checkout Kasir/POS, pencatatan transaksi, pembayaran (tunai/non-tunai), dan pembuatan struk/faktur. Modul ini secara asinkron akan memicu modul Inventori ketika produk terjual.

```mermaid
flowchart TD
    subgraph TransModule ["🛒 Transaction Module"]
        direction TB
        CartSvc[CartCalculationService]
        ChkSvc[CheckoutService]
        PaySvc[PaymentIntegrationService]
        RecSvc[ReceiptGeneratorService]
        
        CartSvc -->|Hitung Subtotal & Pajak| ChkSvc
        ChkSvc -->|Proses Pembayaran| PaySvc
        PaySvc -->|Sukses/Paid| RecSvc
    end
    classDef svc fill:#10b981,stroke:#047857,stroke-width:1px,color:#fff;
    class CartSvc,ChkSvc,PaySvc,RecSvc svc;
```

#### 6. Promo & Bundle Module
Memvalidasi kupon promosi, paket bundle dinamis, dan kalkulasi potongan harga berdasarkan syarat promo sebelum *checkout*.

```mermaid
flowchart TD
    subgraph PromoModule ["🎁 Promo & Bundle Module"]
        direction TB
        PromoRule[PromoValidationService]
        BndlSvc[BundleCalculationService]
        CampSvc[CampaignService]
        
        CampSvc -->|Cek Kupon Aktif| PromoRule
        PromoRule -->|Validasi Syarat Diskon| BndlSvc
    end
    classDef svc fill:#10b981,stroke:#047857,stroke-width:1px,color:#fff;
    class PromoRule,BndlSvc,CampSvc svc;
```

#### 7. Reporting & Analytics Module
Merangkum data penjualan operasional harian, laporan histori pergerakan stok, hingga performa *merchant churn rate* yang disajikan secara terpusat untuk Dashboard atau Cockpit Admin.

```mermaid
flowchart TD
    subgraph ReportModule ["📊 Reporting Module"]
        direction TB
        SalesRep[SalesReportingService]
        InvRep[InventoryReportingService]
        DashAnalytic[DashboardAnalyticsService]
        
        SalesRep -->|Ringkasan Laba/Penjualan| DashAnalytic
        InvRep -->|Timeline Gerak Stok| DashAnalytic
    end
    classDef svc fill:#10b981,stroke:#047857,stroke-width:1px,color:#fff;
    class SalesRep,InvRep,DashAnalytic svc;
```

#### 8. CRM & Customer Support Module
Menyimpan rekam jejak pembeli, mengatur program poin loyalitas (Loyalty Points), serta menyediakan *Customer Support Console* (Ticketing) bagi merchant.

```mermaid
flowchart TD
    subgraph CrmModule ["🤝 CRM Module"]
        direction TB
        CustProf[CustomerProfileService]
        LoyalPt[LoyaltyPointService]
        SupSvc[CustomerSupportService]
        
        CustProf -->|Analisa Riwayat Beli| LoyalPt
    end
    classDef svc fill:#10b981,stroke:#047857,stroke-width:1px,color:#fff;
    class CustProf,LoyalPt,SupSvc svc;
```

## ⚙️ Persyaratan Sistem

Pastikan sistem Anda memenuhi persyaratan berikut sebelum melakukan instalasi:
- **PHP** >= 8.3
- **Composer** (versi terbaru)
- **Node.js** (NPM V22 atau yang lebih baru)
- **Database** (MySQL / PostgreSQL / SQLite)
- **Redis** (Opsional, direkomendasikan untuk environment produksi)

## 📦 Instalasi & Setup

Ikuti langkah-langkah di bawah ini untuk menjalankan proyek di komputer lokal Anda:

1. **Kloning Repositori**
   ```bash
   git clone <url-repo-anda> sollu-app
   cd sollu-app
   ```

2. **Install Dependensi Backend (PHP)**
   ```bash
   composer install
   ```

3. **Install Dependensi Frontend (Node.js)**
   ```bash
   npm install
   ```

4. **Konfigurasi Environment**
   Copy file `.env.example` menjadi `.env` lalu sesuaikan konfigurasi database dan kredensial lainnya.
   ```bash
   cp .env.example .env
   ```
   *Jangan lupa generate application key:*
   ```bash
   php artisan key:generate
   ```

5. **Migrasi dan Seeding Database**
   Jalankan perintah berikut untuk membuat struktur tabel dan mengisi data awal yang dibutuhkan aplikasi:
   ```bash
   php artisan migrate
   php artisan db:seed --class=RolePermissionSeeder
   php artisan db:seed --class=MerchantTypeSeeder
   php artisan db:seed --class=SubscriptionPlanSeeder
   php artisan db:seed --class=UnitSeeder
   php artisan db:seed --class=DummySeeder
   ```

6. **Menjalankan Aplikasi (Development)**
   Buka 2 terminal terpisah dan jalankan perintah berikut:

   Terminal 1 (Menjalankan server Laravel):
   ```bash
   php artisan serve
   ```

   Terminal 2 (Menjalankan Vite asset bundler):
   ```bash
   npm run dev
   ```
   
   Aplikasi dapat diakses melalui `http://localhost:8000`.

## 📜 Lisensi
Aplikasi ini bersifat tertutup (Proprietary). Hak cipta dilindungi.
