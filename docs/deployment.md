# Sollu App Deployment & Infrastructure Documentation

Panduan resmi arsitektur deployment, konfigurasi multi-proses kontainer, strategi logging, penjadwalan cron, dan alur CI/CD untuk lingkungan produksi **Sollu App**.

---

## 1. Arsitektur Kontainer & Runtime Monolith

Sollu App menggunakan arsitektur *Single Container Modular Monolith* berbasis **Alpine Linux (PHP 8.3 FPM + Nginx + Supervisord)** yang didukung oleh container **Redis** untuk antrean (queue) dan caching.

```
+-----------------------------------------------------------------------------------+
| Host Server (Ubuntu / Linux)                                                      |
|                                                                                   |
|  +-----------------------------------------------------------------------------+  |
|  | Container: sollu-app (ghcr.io/whykrr/sollu-app:latest)                      |  |
|  |                                                                             |  |
|  |  Supervisord (PID 1)                                                        |  |
|  |    |                                                                        |  |
|  |    +---> [program:nginx]           (Port 80 HTTP Proxy -> /dev/stdout)     |  |
|  |    +---> [program:php-fpm]         (Port 9000 FastCGI -> /dev/stdout)      |  |
|  |    +---> [program:queue-worker]    (Redis Queue -> storage/logs/worker.log) |  |
|  |    +---> [program:reverb]          (Port 8080 WS -> storage/logs/reverb.log)|  |
|  |    +---> [program:cron]            (Alpine crond -> storage/logs/schedule)  |  |
|  +-----------------------------------------------------------------------------+  |
|                                         |                                         |
|                                         v (sollu-network)                         |
|  +-----------------------------------------------------------------------------+  |
|  | Container: sollu-redis (redis:7-alpine)                                     |  |
|  |  Data Volume: sollu-redis-data:/data                                        |  |
|  +-----------------------------------------------------------------------------+  |
+-----------------------------------------------------------------------------------+
```

### Karakteristik & Port Mapping:
- **Port 8002 (Host) $\rightarrow$ 80 (Container):** Web HTTP endpoint (Nginx $\rightarrow$ PHP-FPM).
- **Port 8080 (Host) $\rightarrow$ 8080 (Container):** WebSocket server (Laravel Reverb untuk real-time update kasir/POS).
- **Isolated Bridge Network (`sollu-network`):** Komunikasi aman antar-kontainer (App $\leftrightarrow$ Redis $\leftrightarrow$ Postgres Core).

---

## 2. Multi-Process Supervisor & Graceful Termination

Supervisor bertindak sebagai init process (PID 1) di dalam container `sollu-app`. Seluruh subproses dikonfigurasi dengan perlindungan *Graceful Termination* agar tidak terjadi *corrupted state* pada data atau transaksi saat container di-restart atau di-deploy.

### Konfigurasi Sinyal & Timeout (`docker/supervisord.conf`)

| Program | Command | Stop Signal | Stop Wait Secs | Log Output |
| :--- | :--- | :--- | :--- | :--- |
| **`php-fpm`** | `php-fpm` | `QUIT` | 30s | `/dev/stdout` & `/dev/stderr` |
| **`nginx`** | `nginx -g 'daemon off;'` | `QUIT` | 10s | `/dev/stdout` & `/dev/stderr` |
| **`horizon`** | `php artisan horizon` | `SIGTERM` | **3600s** (1 jam) | `storage/logs/horizon.log` |
| **`reverb`** | `php artisan reverb:start ...` | `SIGTERM` | 15s | `storage/logs/reverb.log` |
| **`cron`** | `crond -f -l 2` | `SIGTERM` | 10s | `/dev/stdout` |

### Penjelasan Standar Graceful Shutdown:
1. **Laravel Horizon (`stopwaitsecs=3600`, `stopasgroup=true`, `killasgroup=true`):**
   - Laravel Horizon menangkap sinyal `SIGTERM` melalui ekstensi PHP `pcntl` dan berhenti mengambil job baru dari Redis, lalu menunggu job yang sedang aktif (misal: generate PDF/Excel laporan transaksi atau sinkronisasi multi-outlet) selesai dieksekusi.
   - Durasi `stopwaitsecs=3600` menjamin job panjang tidak dibunuh paksa di tengah jalan.
   - `stopasgroup=true` & `killasgroup=true` memastikan seluruh subprocess worker turunan ikut dihentikan secara teratur tanpa meninggalkan *zombie process*.
2. **Nginx & PHP-FPM (`stopsignal=QUIT`):**
   - Menggunakan sinyal `QUIT` (graceful shutdown) agar Nginx dan PHP-FPM menyelesaikan seluruh HTTP request aktif sebelum menutup socket koneksi.
3. **Docker Engine Coordination (`stop_grace_period: 60s`):**
   - Diatur pada `docker-compose.prod.yml`. Docker daemon akan memberi tenggang waktu hingga 60 detik kepada container sebelum mengirim `SIGKILL` saat eksekusi `docker stop` atau `docker compose up -d`.

---

## 3. Strategi Logging & Log Rotation

Untuk mencegah *log noise* dan polusi stdout Docker:

### A. Aliran Log Utama (Docker Stdout / Stderr)
- **Nginx & PHP-FPM:** Output diarahkan ke `/dev/stdout` dan `/dev/stderr`.
- Perintah `docker compose logs -f sollu-app` hanya menampilkan trafik akses web, request status code, dan critical system errors.

### B. Aliran Log Terisolasi (Storage Logs)
Job background dan event WebSocket dialihkan ke berkas khusus di dalam `storage/logs/` dengan rotasi bawaan Supervisor:

- **Laravel Horizon:**
  - Standard Log: `storage/logs/horizon.log` (`maxbytes=10MB`, `backups=5`)
  - Error Log: `storage/logs/horizon-error.log` (`maxbytes=10MB`, `backups=5`)
- **Laravel Reverb:**
  - Standard Log: `storage/logs/reverb.log` (`maxbytes=10MB`, `backups=3`)
  - Error Log: `storage/logs/reverb-error.log` (`maxbytes=10MB`, `backups=3`)
- **Schedule Cron:**
  - Log Output: `storage/logs/schedule.log`
- **Structured JSON Logs (Grafana Loki):**
  - Log Output: `storage/logs/laravel-json-YYYY-MM-DD.log`

> [!TIP]
> Log di `storage/logs/` dapat diinspeksi secara real-time dari host tanpa mengotori `docker logs`:
> ```bash
> docker compose exec sollu-app tail -f storage/logs/horizon.log
> docker compose exec sollu-app tail -f storage/logs/reverb.log
> ```

---

## 4. Penjadwalan Tugas (Linux System Cron vs `schedule:work`)

Di lingkungan produksi, **DILARANG KERAS** menggunakan `php artisan schedule:work`.

### Mengapa Linux Cron (`crond`) Lebih Unggul?
1. **Zero Time Drift:** `schedule:work` menggunakan loop `sleep()` di PHP yang mengakumulasi delay mikrodetik seiring waktu. Linux `crond` membaca clock kernel OS sehingga eksekusi selalu presisi pada detik ke-0 setiap menit.
2. **Zero Memory Leak:** Setiap menit, `crond` mengeksekusi proses PHP baru (`php artisan schedule:run`) yang langsung terminasi dan melepaskan seluruh alokasi memori setelah tugas selesai.
3. **Process Isolation:** Jika suatu task mengalami fatal exception atau timeout, proses loop scheduler induk tidak akan mati.

### Konfigurasi Crontab Kontainer:
Didaftarkan di `/etc/crontabs/root` pada saat build Docker image:
```cron
* * * * * su -s /bin/sh www-data -c 'php /var/www/html/artisan schedule:run --no-interaction' >> /var/www/html/storage/logs/schedule.log 2>&1
```

---

## 5. Alur CI/CD Deployment (GitHub Actions)

Alur deployment otomatis dikonfigurasi melalui `.github/workflows/deploy.yml` dan dipicu saat ada push Git Tag (`v*.*.*`) atau branch `master`:

```
   Git Push (Tag v1.x.x)
             │
             ▼
┌───────────────────────────┐
│   GitHub Actions Runner   │
│ 1. Docker Buildx (Cache)  │
│ 2. Push Image ke GHCR     │
└─────────────┬─────────────┘
              │ (SSH Action)
              ▼
┌───────────────────────────┐
│     Production Server     │
│ 1. docker compose pull    │
│ 2. docker compose up -d   │
│ 3. php artisan migrate    │
│ 4. php artisan db:seed    │
│ 5. php artisan optimize   │
│ 6. horizon:terminate      │
│ 7. queue:restart          │
│ 8. docker image prune     │
└───────────────────────────┘
```

### Langkah Eksekusi Deployment di Server:
```bash
# 1. Tarik image terbaru dari GitHub Container Registry
docker compose -f docker-compose.prod.yml pull

# 2. Re-create container dengan graceful timeout
docker compose -f docker-compose.prod.yml up -d

# 3. Jalankan migrasi database & seeder (termasuk schema sollu_pulse)
docker compose -f docker-compose.prod.yml exec -T sollu-app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec -T sollu-app php artisan db:seed --force

# 4. Refresh dan optimalkan cache framework
docker compose -f docker-compose.prod.yml exec -T sollu-app php artisan config:clear
docker compose -f docker-compose.prod.yml exec -T sollu-app php artisan route:clear
docker compose -f docker-compose.prod.yml exec -T sollu-app php artisan view:clear
docker compose -f docker-compose.prod.yml exec -T sollu-app php artisan optimize

# 5. Graceful restart Horizon & Queue worker agar membaca kode terbaru
docker compose -f docker-compose.prod.yml exec -T sollu-app php artisan horizon:terminate || true
docker compose -f docker-compose.prod.yml exec -T sollu-app php artisan queue:restart

# 6. Bersihkan image lama yang sudah tidak terpakai
docker image prune -f
```

---

## 6. Healthcheck & Monitoring

Container `sollu-app` dilengkapi dengan healthcheck bawaan Laravel 11 (`/up` endpoint):

```dockerfile
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://127.0.0.1/up || exit 1
```

- **Interval:** Diperiksa setiap 30 detik.
- **Start Period:** Toleransi 10 detik pertama saat proses inisialisasi boot.
- **Fail Retries:** Jika 3x berturut-turut gagal merespons, Docker akan menandai status container sebagai `unhealthy`.

---

## 7. Panduan Pemecahan Masalah (Troubleshooting)

### A. Memeriksa Status Multi-Proses Supervisor
```bash
docker compose exec sollu-app supervisorctl status
```
Output yang diharapkan:
```
cron                             RUNNING   pid 14, uptime 2 days, 4:12:00
nginx                            RUNNING   pid 11, uptime 2 days, 4:12:00
php-fpm                          RUNNING   pid 10, uptime 2 days, 4:12:00
queue-worker                     RUNNING   pid 12, uptime 2 days, 4:12:00
reverb                           RUNNING   pid 13, uptime 2 days, 4:12:00
```

### B. Me-restart Service Tertentu Tanpa Merestart Kontainer
```bash
# Restart queue worker saja
docker compose exec sollu-app supervisorctl restart queue-worker

# Restart reverb websocket
docker compose exec sollu-app supervisorctl restart reverb

# Reload konfigurasi nginx
docker compose exec sollu-app nginx -s reload
```

### C. Memeriksa Antrean Job yang Gagal
```bash
docker compose exec sollu-app php artisan queue:failed
docker compose exec sollu-app php artisan queue:retry all
```
