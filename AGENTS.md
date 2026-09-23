<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.3. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:

- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Architecture & Project Documentation (MUST READ FIRST)

When diving into this project, you MUST refer to the official architecture documentation in the `docs/` directory:

- [docs/architecture.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/architecture.md) — System Overview, Subdomain Routing, Modular Monolith & Decoupling Patterns, Execution Flow, Performance & On-Demand Data Loading Baseline.
- [.agents/rules/01-user-experience.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/01-user-experience.md) — Core Philosophy (*"Jangan membuat user belajar cara kerja aplikasi; buat aplikasi mengikuti cara kerja user"*) & 15 User-Centric Engineering Principles.
- [docs/database.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/database.md) — Multi-Tenant Data Isolation (`business_id`, `outlet_id`), Model Standards (Laravel 11 `casts(): array`), Core Bounded Context Schemas, and Query Optimization.
- [docs/authorization.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/authorization.md) — Dual-Layer Authorization: User RBAC (Spatie Permissions scoped to `business_id`, `useAuth`) and SaaS Feature Plan Gating (`FeatureEnum`, `PlanEnum`, `<FeatureLock>`, `usePlanFeature`).
- [docs/frontend.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/frontend.md) — Frontend Standards: Vue 3 (`<script setup>`), Inertia.js 1.2, Tailwind CSS v4, `@/Components/Form/` (Zero Raw HTML inputs), `<PopUpPage>` & `usePopUpStore` Drawer Pattern, Enum-Driven UI.
- [docs/ui-ergonomics.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/ui-ergonomics.md) — Multi-Device Ergonomics: Breakpoint Matrix, Touch Targets ($\ge 44\text{px}$ mobile, $\ge 36\text{px}$ tablet, $\ge 28\text{px}$ desktop), iOS Anti-Zoom (`.form.adaptive`), Thumb Zone, and Responsive Column Masking.
- [docs/ux-wording.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/ux-wording.md) — UX Copywriting & Tone of Voice: Santai, komunikatif, to-the-point, profesional, contextual UI microcopy (Empty State, Toast, Modal, Forms).
- [docs/api.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/api.md) — API Standards: `snake_case`, pure HTTP status codes, strict `(float)` numeric casting, Response Constants (`ResourceMessage`), Async Excel & PDF Generation, and OpenAPI/Postman synchronization.
- [docs/testing.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/testing.md) — Automated Testing: Service Unit Tests (100% Mocking, `sqlite:memory`), Feature/Tenant Isolation Tests, E2E Testing via `browsermcp`, Linters (Pint, ESLint), and Definition of Done (DoD).
- [docs/changelog.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/docs/changelog.md) — Changelog & Versioning Standards: Keep a Changelog v1.1.0, Semantic Versioning (`vMAJOR.MINOR.PATCH`), Git Tagging Workflow, and Conventional Commit mapping.

## Core Application Rules Hierarchy (MANDATORY TO FOLLOW)

The rules in `.agents/rules/` are ordered from fundamental philosophy to technical implementation. All agents MUST read and follow these rules unconditionally:

1. [01-user-experience.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/01-user-experience.md) — Core Philosophy & 15 User-Centric Engineering Principles.
2. [02-ux-wording.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/02-ux-wording.md) — UX Copywriting & Tone of Voice (Santai, komunikatif, to-the-point, profesional).
3. [03-modular-architecture.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/03-modular-architecture.md) — Modular Monolith, Bounded Contexts, Zero Cross-Table Mutation & Multi-Tenant Data Isolation.
4. [04-auth-and-enums.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/04-auth-and-enums.md) — Single Source of Truth Enums, Zero-Orphan Permissions & Dual-Layer Authorization (RBAC vs SaaS Feature Plan).
5. [05-frontend-standards.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/05-frontend-standards.md) — MainPage Layout, ActionBar, Flat Minimalist (Zero Shadows), Table, 3-Tier Forms, useFormDirtyGuard.
6. [06-backend-standards.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/06-backend-standards.md) — PHP 8.3 & Laravel 11, Multiline Chaining, Eloquent Resources, Pint & ESLint.
7. [07-tooling-and-testing.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/07-tooling-and-testing.md) — MCP Tooling Standards (Git, Laravel Boost, sollu-db read-only) & Automated Testing Enforcement.
8. [08-git-and-changelog.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/08-git-and-changelog.md) — Keep a Changelog, Semantic Versioning & Annotated Git Releases.
9. [10-domain-inventory.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/10-domain-inventory.md) — Inventory Module Business Rules (FIFO/Average Costing, Immutable Ledger, SoD).
10. [11-domain-notifications.md](file:///Users/whykrr/Documents/Projects/Laravel/sollu-app/.agents/rules/11-domain-notifications.md) — Notification System Standards (BaseNotification, Multi-Level Scoping, 4-Tab Popover).


## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## MCP Tooling Standards & Workflow Optimization

This workspace provides configured Model Context Protocol (MCP) servers. Agents MUST prioritize and optimize the use of MCP tools according to these standards:

- **Git (`git-mcp-server`):** Standard tool for all version control tasks (`status`, `add`, `commit`, `branch_list`, `branch_create`, `checkout`, `stash_save`, `stash_pop`).
    - **MANDATORY:** Prioritize MCP Git over raw shell `git` commands in terminal runners.
    - **Commit Format:** Strictly use Conventional Commits (`feat(module):`, `fix(module):`, `refactor(module):`, `style:`, `test:`, `chore:`).
    - **Atomic & Verified:** Never commit broken code; verify with linters (`pint`, `eslint`), tests (`phpunit`), and UI verification (`browsermcp`) before committing.
    - **Safe Working Tree:** Check `status` before branch operations; use `stash_save` rather than discarding unstaged work.
- **Laravel Boost (`laravel-boost`):** Use `database-schema` to inspect database structure, `last-error` and `read-log-entries` for instant runtime troubleshooting, and `search-docs` for official Laravel/Inertia documentation.
- **Database Inspection (`sollu-db`):**
    - Use `sollu-db` (`server-postgres`) for read-only (`SELECT`) data inspection, tenant data validation (`business_id`, `outlet_id`), and constraint audits in `sollu_core`. DIRECT DDL/DML MUTATIONS VIA MCP ARE STRICTLY FORBIDDEN.
- **Filesystem (`filesystem`):** Structured directory and file inspections when complementary to built-in tools.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Test every code change by adding or updating a test.
- Run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v1

- Inertia v1 does not support the following v2 features: deferred props, infinite scrolling (merging props + `WhenVisible`), lazy loading on scroll, polling, or prefetching. Do not use these.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v11 rules ===

# Laravel 11

- Laravel 11 brought a new streamlined file structure which this project now uses.

## Laravel 11 Structure

- In Laravel 11, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- No app\Console\Kernel.php - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Commands auto-register - files in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

## New Artisan Commands

- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class`
    - `php artisan make:interface`

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.

- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
