---
name: domain-subscription
description: >-
    Knowledge and standards for Subscription, Trial, and Billing Quotas in Sollu App.
    Covers Subscription, SubscriptionPlan, SubscriptionOutlet, One-Trial Policy, Multi-Outlet
    quota reconciliation, downgrade conflict wizard, payment wall (subscription_lock), and testing practices.
    Activate whenever creating, modifying, or testing subscriptions, plans, trials, billing, or outlet quota limits.
---

# Domain Skill: Subscription, Trial & Quota Management

Pedoman baku domain langganan (*subscription*), kuota cabang, kebijakan uji coba (*trial*), dan penguncian tagihan (*payment wall*) di **Sollu App**.

---

## 1. Single Source of Truth Status Langganan & Trial

1. **Eliminasi Kolom Legacy:**
   - Kolom `trial_end_at` pada tabel `businesses` telah di-drop dan dilarang digunakan kembali.
   - Status langganan dan trial **100% bersumber dari tabel `subscriptions`** yang berelasi ke `SubscriptionPlan`.
2. **Kondisi Aktif Valid:**
   - Subscription berstatus `SubscriptionStatus::Active` (`'active'`).
   - Tanggal `expired_at` bernilai `null` atau di masa depan (`expired_at >= now()`).
3. **Penyimpanan Cache & Invalidation:**
   - `Business::getActiveSubscriptionWithPlan()` di-cache selama 1 jam (`business:{id}:active_subscription`).
   - **Wajib Invalidate:** Setiap mutasi status subscription atau pergantian paket wajib memanggil `Business::clearActiveSubscriptionCache($business->id)`.

---

## 2. One-Trial Policy & Eliminasi Trial saat Upgrade

1. **One-Trial Session:**
   - Kolom boolean `businesses.has_used_trial` menandai apakah suatu bisnis pernah menikmati masa uji coba.
   - Merchant yang memiliki `has_used_trial = true` tidak boleh diberikan masa trial kedua pada paket apapun.
2. **Eliminasi Trial Seketika saat Upgrade:**
   - Jika merchant yang sedang aktif masa trial memutuskan untuk upgrade atau berganti paket berbayar, subscription trial lama **langsung diubah menjadi `canceled` (`canceled_at = now()`)**.
   - Sisa hari trial gugur seketika, dan subscription baru dibuat dalam status berbayar reguler (`is_trial = false`).

---

## 3. Kuota Multi-Outlet & Downgrade Conflict Resolution

1. **Batas Kuota Paket (`max_outlets`):**
   - Kolom `subscription_plans.max_outlets`: `null` menandakan tak terbatas (*unlimited*), integer $\ge 1$ menandakan batas kuota aktif.
   - Pengecekan kuota aktif berlaku saat **pembuatan cabang baru** (`CreateOutletService`) dan **pengaktifan kembali cabang nonaktif** (`ManageOutletStatusService::toggleStatus`).
2. **Rekonsiliasi saat Downgrade:**
   - Jika merchant memiliki cabang aktif melebihi kuota paket tujuan, merchant **wajib memilih cabang yang tetap aktif** via payload `selected_outlet_ids`.
   - Cabang yang tidak dipilih diubah menjadi `is_active = false`, serta `subscription_outlets` lama ditandai `deactivated_at = now()`.
   - **Integritas Data:** Data riwayat penjualan, inventori, dan stok pada cabang yang dinonaktifkan **TIDAK BOLEH DIHAPUS**. Cabang dapat diaktifkan kembali jika merchant melakukan upgrade paket di masa mendatang.
   - Invoice recurring dihitung hanya untuk outlet yang tetap aktif.

---

## 4. Payment Wall Informatif (`subscription_lock`)

1. **Shared Prop Inertia:**
   - Disediakan melalui `HandleAppInertiaRequests::share` dengan struktur:
     ```php
     'subscription_lock' => [
         'is_locked' => bool,
         'lock_reason' => 'payment_pending' | 'trial_expired' | 'subscription_expired' | null,
         'invoice_url' => string | null,
         'plans_url' => string | null,
     ]
     ```
2. **Pengecualian Rute (Exempt Routes):**
   - Halaman Billing (`/settings/billing/*`) dan Profil (`/settings/profile/*`) **bebas dari pemblokiran modal** agar merchant dapat melihat invoice, memilih metode pembayaran, atau berganti paket tanpa hambatan.
   - Pada halaman operasional toko (POS, produk, stok, laporan, dll), `SubscriptionLockModal.vue` akan tampil memandu pemilik toko menyelesaikan pembayaran.

---

## 5. Konvensi Pengujian & Traps

1. **Route Model Binding Outlet:**
   - Parameter `{outlet}` pada rute controller mengikat UUID (`id`), bukan `slug`.
2. **Pengujian POS Device:**
   - Endpoint POS canonical berada di domain `api.sollu.test` (`config('domain.api')`).
   - Request pengujian wajib menyertakan header `X-DEVICE-UUID` dan `X-HARDWARE-SIGNATURE`.
   - Daftarkan device ke cache via `app(PosDeviceAuthCacheService::class)->putDevice($device)` sebelum `Sanctum::actingAs($device, ['pos:access'])`.
3. **Outlet Inactive Guard:**
   - Jika outlet dinonaktifkan (`is_active = false`), endpoint POS device wajib menolak dengan HTTP 403 dan JSON `error_code: OUTLET_INACTIVE`.
