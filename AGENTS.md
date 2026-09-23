# Sollu App - AI Agent Guidelines

## 1. Project Context & Stack
- **Backend:** Laravel 11, PHP 8.3 (Strict types, constructor promotion, `casts(): array`).
- **Frontend:** Vue 3 (Script Setup), Inertia.js 1.2 SPA, Tailwind CSS v4.
- **Database:** PostgreSQL (Multi-tenant scoped by `business_id` & `outlet_id`).
- **Realtime:** Laravel Reverb & Echo WebSocket.

---

## 2. Core Rules Hierarchy (`.agents/rules/`)
Before designing or modifying code, ensure compliance with the standing rules:
- `01-ux-and-wording.md`: 15 User-Centric Principles, Zero-Shadow, Tone of Voice.
- `02-modular-architecture.md`: Bounded contexts, tenant isolation, anti-overfetching.
- `03-auth-and-enums.md`: PHP Enums SSOT (`$enums`), Dual-Layer Auth (`v-can` vs `v-feature`).
- `04-frontend-standards.md`: `MainPage` 5-slot layout, `ActionBar`, form `sm` (30px), 3-Tier Forms, `useFormDirtyGuard`.
- `05-backend-standards.md`: PHP 8.3 multiline chaining, thin controllers, `ResourceMessage`.
- `06-tooling-and-testing.md`: Git MCP, 100% Mocking Service Unit Tests, Definition of Done.
- `07-git-and-changelog.md`: SemVer, Conventional Commits, Keep a Changelog.

---

## 3. On-Demand Domain Skills (`.agents/skills/`)
Activate the relevant specialized skill when working in specific domains:
- `domain-inventory`: Stock balances, FIFO layers, movements, PO, GR, transfers, opname, SoD.
- `domain-notifications`: `BaseNotification`, multi-tenant scoping, mail, broadcast, retention.
- `domain-audit-log`: ActivityLoggerInterface, table partitioning, event matrices.
- `sollu`: Comprehensive architecture & technical implementation reference.
- `inertia-vue-development`, `testing-best-practices`, `configuring-horizon`, `echo-development`.

---

## 4. Essential Commands & Verification
- **PHP Formatter:** `vendor/bin/pint --dirty` (Run before committing PHP code).
- **Frontend Lint:** `npm run lint` & `npm run format`.
- **Run Tests:** `php artisan test --compact --filter=TestName`.
- **Database Inspection:** Use `sollu-db` (SELECT only) or `laravel-boost` `database-schema`.
