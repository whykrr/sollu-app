# Copilot Instructions for Sollu App

## Project Overview

-   **Sollu App** is a Laravel-based SaaS Point of Sale (POS) system for multi-outlet businesses.
-   Merchants can manage multiple outlets, each with flexible subscription plans.
-   The backend is Laravel (PHP 8.3+), the frontend uses Vue 3 with Inertia.js, and Tailwind CSS for styling.

## Key Architecture & Patterns

-   **Domain Structure:**
    -   `app/Models/` — Eloquent models for core entities (Merchant, Outlet, Subscription, etc.)
    -   `app/Http/Controllers/` — API and web controllers, grouped by domain
    -   `app/Http/Requests/` — Form validation logic
    -   `app/Helpers/` — Utility functions for business logic
    -   `resources/js/Pages/` — Vue 3 pages, organized by dashboard section
    -   `resources/js/Pages/Dashboard/Product/Categories/` — Product category CRUD (see `Create.vue` for form pattern)
-   **Frontend:**
    -   Uses Inertia.js for SPA-like navigation with Laravel backend
    -   Vue components are split into pages and partials (e.g., `CategoryForm.vue`)
    -   Form handling via Inertia's `useForm` pattern
-   **Backend:**
    -   Follows Laravel conventions for routing, controllers, and models
    -   Uses Laravel's seeder system for initial data (see `README.md` for commands)

## Developer Workflows

-   **Setup:**
    -   Requires PHP 8.3+, NPM v22+
    -   Run migrations and seeders as shown in `README.md`
-   **Build & Assets:**
    -   Use Vite for asset bundling (`vite.config.js`)
    -   Tailwind CSS config in `tailwind.config.js`
-   **Testing:**
    -   Tests are in `tests/Feature/` and `tests/Unit/`
    -   Use `php artisan test` for running tests
-   **Debugging:**
    -   Laravel Debugbar and Telescope are available (see `config/debugbar.php`, `config/telescope.php`)

## Project-Specific Conventions

-   **Form Handling:**
    -   Use Inertia's `useForm` for all Vue forms
    -   Validation handled server-side via Laravel Form Requests
-   **Authorization:**
    -   Policies in `app/Policies/`
    -   Permission logic in `config/permission.php`
-   **Seeding:**
    -   Custom seeders for roles, merchants, plans, and demo data
-   **Naming:**
    -   Use English for code, Bahasa Indonesia for UI labels

## Integration Points

-   **Payments:**
    -   Integrated with Midtrans (see `config/midtrans.php`)
-   **Notifications:**
    -   Laravel Notifications in `app/Notifications/`
-   **Mail:**
    -   Custom mailers in `app/Mail/`

## Examples

-   **Category Creation:**
    -   See `resources/js/Pages/Dashboard/Product/Categories/Create.vue` for Inertia form + Laravel endpoint
-   **Seeder Usage:**
    -   `php artisan db:seed --class=DummySeeder`

---

For more details, see `README.md` and config files in `/config`.
