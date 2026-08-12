---
name: sollu-core-architecture
description: >-
  Core system architecture, technology stack specifications (Laravel 11.9+, PHP 8.3, Vue 3 Composition API,
  Inertia.js 1.2, Tailwind CSS v4, Vite 6), directory layout standards, security baseline, and UI localization
  guidelines (Indonesian UI text) for Sollu App. MUST trigger when setting up core modules, reviewing project architecture,
  or understanding overall system conventions.
---

# Sollu Core Architecture & Tech Stack

Standard arsitektur, teknologi, dan fondasi pengembangan aplikasi **Sollu App**.

## 1. Official Technology Stack

- **Backend Framework:** Laravel 11.9+ (`laravel/framework` ^11.9, PHP 8.3)
- **Frontend Engine:** Vue 3 (Composition API `<script setup>`), Inertia.js 1.2 (`@inertiajs/vue3` ^1.2.0, `inertia-laravel` ^1.3)
- **Styling & Build Tool:** Tailwind CSS v4 (`@tailwindcss/postcss` ^4.1.11, `tailwindcss` ^4.1.11), Vite 6 (`vite` ^6.3.5)
- **Core Packages & State:** Pinia (^2.3.0), Ziggy (^2.3), Spatie Laravel Permission (^6.20), FontAwesome 6 (^6.7.1)
- **Additional UI Libraries:** Swiper 11, Quill 2, Chart.js 4, vue-advanced-cropper 2, vuedraggable 4, vue-i18n 11

## 2. Directory Layout & Layer Responsibilities

```
app/
├── Http/
│   ├── Controllers/     # Thin controllers handling request, authorization & response
│   └── Requests/        # Form Requests extending BaseInertiaFormRequest
├── Models/              # Eloquent models using HasUuids trait and casts() method
├── Services/            # Domain service logic (Single-file <= 500 lines or Split-file > 500 lines)
├── Jobs/
│   └── ImportExport/    # Async CSV jobs (AbstractCsvExportJob & AbstractCsvImportJob)
├── Enums/               # PermissionEnum, RoleEnum, Status enums
resources/
├── js/
│   ├── Components/      # Shared UI & Form inputs (@/Components/Form/)
│   ├── Pages/           # Inertia page views (Pages/{Module}/Components & Tabs)
│   ├── store/           # Pinia stores (usePopUpStore, useModalStore, useToastStore, useAppStore)
│   └── Composable/      # Vue composables (useAuth)
└── views/
    └── pdf/             # DomPDF Blade templates & pdf.partials.header
```

## 3. Localization & Language Rules

- **UI Text & Error Messages:** MUST strictly use **Indonesian** (e.g. `"Anda tidak memiliki akses."`, `"Data berhasil disimpan."`).
- **Code Documentation & Comments:** Code comments, docstrings, variable names, and architectural rules MUST be written in **English**.

## 4. Security & Isolation Baseline

- **Tenant Isolation:** Enforce `business_id` or `outlet_id` checks on every query mutation.
- **ORM Enforcements:** Use Eloquent or Query Builder bindings exclusively. Never construct raw SQL strings with inline variable interpolations.
- **Environment Secrets:** Store secret keys, webhooks, and API credentials exclusively in `.env`. Never commit secrets directly in code.
