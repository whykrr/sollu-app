# Sollu App - AI Agent Guidelines

## 1. Project Context & Stack
- **Backend:** Laravel 12, PHP 8.3 (Strict types, constructor promotion, `casts(): array`).
- **Frontend:** Vue 3 (Composition API `<script setup>`), Inertia.js 1.2 SPA, Tailwind CSS v4.
- **Database:** PostgreSQL 16+ (Multi-tenant scoped by `business_id` & `outlet_id`, table partitioning).
- **Realtime & Queue:** Laravel Reverb (WebSocket), Laravel Horizon (Redis Queue), Laravel Pulse (Observability).

---

## 2. Core Rules Hierarchy (`.agents/rules/`)
Before designing or modifying code, ensure compliance with the standing rules:
- `01-ux-and-wording.md`: 15 User-Centric Principles, Zero-Shadow, Tone of Voice.
- `02-modular-architecture.md`: Bounded contexts, tenant isolation, root user notification scope, anti-overfetching.
- `03-auth-and-enums.md`: PHP Enums SSOT (`$enums`), Dual-Layer Auth (`v-can` vs `v-feature`).
- `04-frontend-standards.md`: `MainPage` 5-slot layout, `ActionBar`, form `sm` (30px), 3-Tier Forms, `useFormDirtyGuard`.
- `05-backend-standards.md`: PHP 8.3 multiline chaining, thin controllers, `ResourceMessage`.
- `06-tooling-and-testing.md`: Git MCP, Pragmatic 5-Layer Testing Architecture, Zero-Error Data & Logic Integrity, Definition of Done.
- `07-git-and-changelog.md`: SemVer, Conventional Commits, Keep a Changelog, Git Tag SSOT production baseline.
- `08-database-and-migrations.md`: Git Tag production baseline immutability, backward compatibility, bidirectional migrations, absolute rollback symmetry, phased ETL, rollback verification.

---

## 3. On-Demand Domain Skills (`.agents/skills/`)
Activate the relevant specialized skill when working in specific domains:
- `domain-product`: Unified Item Pattern, ProductItem pivot variant options, inventory decoupling, SaaS 4-tier gating, 2-level category, RawMaterial, Recipe BOM, Modifier, and Omni-Search.
- `domain-inventory`: Stock balances, FIFO layers, movements, PO, GR, transfers, opname, SoD.
- `domain-subscription`: Subscription lifecycle, One-Trial Policy, multi-outlet quotas, downgrade wizard, payment wall, and billing reconciliation.
- `domain-access-and-feature-gating`: 6-layer gating stack, SaaS plan feature gating, User RBAC, caching fast-path, and UI duality.
- `domain-notifications`: `BaseNotification`, multi-tenant scoping, mail, broadcast, retention.
- `domain-audit-log`: ActivityLoggerInterface, table partitioning, event matrices.
- `sollu`: Comprehensive architecture & technical implementation reference.
- **Stack Skills:** `inertia-vue-development`, `laravel-best-practices`, `tailwindcss-development`, `query-optimization`, `testing-best-practices`, `configuring-horizon`, `echo-development`, `pulse-development`, `telescope-debugging`.

---

## 4. Essential Commands & Verification
- **PHP Formatter:** `vendor/bin/pint --dirty` (Run before committing PHP code).
- **Frontend Lint:** `npm run lint` & `npm run format`.
- **Run Tests:** `php artisan test --compact --filter=TestName`.
- **Rollback Verification:** `php artisan migrate:rollback --step=1 && php artisan migrate` (Wajib saat membuat/mengubah migration).
- **Database Inspection:** Use `laravel-boost` `database-schema` or `database-query`.
- **Project Inspection:** Gunakan MCP `sollu-project` untuk inspeksi project (enum, tenant, fitur, inventory, rule lints, migration compatibility & Git Tag production baseline).

---

## 5. Scratchpad / Planning / Context & Active Clarification
- **Protokol Turn Pertama & /goal (READ-ONLY):** Turn pertama atau setiap pemanggilan perintah `/goal` SELALU berstatus **READ-ONLY**. Dilarang keras memodifikasi, membuat, atau menghapus file apa pun sebelum artefak rencana (`/plan`) disetujui.
- **Eksplorasi & Perencanaan Wajib:** Setiap tugas baru WAJIB diawali dengan eksplorasi kode menyeluruh dan pembuatan `/plan` terstruktur (asumsi, dependensi terdampak, dan batasan arsitektur).
- **Persetujuan Eksplisit Sebelum Mutasi:** Tunggu persetujuan eksplisit (seperti *"Lanjutkan"* atau *"Eksekusi"*) dari pengguna sebelum memanggil tool penulisan/modifikasi file atau eksekusi perintah terminal yang merusak/mengubah state.
- **Klarifikasi Aktif:** Jika ada informasi kritis, API contract, atau edge case yang ambigu, jangan menebak atau mengasumsikan implementasi. Kumpulkan pertanyaan tersebut dalam daftar poin terstruktur dan berikan rekomendasi opsi terbaik untuk dikonfirmasi sebelum melanjutkan.
