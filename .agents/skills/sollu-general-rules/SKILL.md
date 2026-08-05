---
name: sollu-general-rules
description: General rules, verification steps, security, roles, permissions, and definition of done for the Sollu app project. Trigger this when starting a new task, reviewing code, or finishing up a task.
---

# Sollu General Rules

## Tech Stack Reference

- **Backend:** Laravel 11, PHP 8.3
- **Frontend:** Vue 3 (Composition API), Inertia.js 1.2, Tailwind CSS v4
- **Packages:** Pinia, Ziggy, Spatie Permission, FontAwesome 6

## Before Coding

- Always read the relevant files **completely** (not just snippets) before making changes.
- Check dependencies, traits, and services used by the file.
- Check the latest migrations for table structures related to the task.
- Check if similar helpers/services/components exist before creating new ones.
- Do not modify code unrelated to the task.
- Do not perform major refactoring without explicit instructions.
- Do not remove existing features unless requested.
- If requirements are ambiguous, **ask first** — never make your own assumptions and proceed with major implementations.
- Prioritize following existing patterns in the project.
- Avoid creating new dependencies unless absolutely necessary.
- Avoid duplicate code.

## Verification Rules

- Before calling existing methods/functions/traits, **you must read their definitions first**. Do not assume signatures from names.
- Before using database columns, **you must check the migration or related model**. Do not assume column names (e.g., `user_id` vs `created_by`).
- Before calling routes, **you must check `routes/web.php` or `routes/api.php`**. Do not invent route names.
- Before using packages/libraries, **you must check `composer.json` or `package.json`** for installation and versions. Do not assume package availability.
- If unsure if a file/function/column exists, explicitly state "needs verification" and read the related file — do not guess and proceed.
- Do not create API response examples, env variable names, or config values that are not confirmed to exist in the project (e.g., `.env.example`, `config/*.php`).

## Referencing Existing Code

- When mentioning "there is an existing pattern in the project", you **must include the file path and function/class name**.
- Do not say "usually in Laravel..." or "generally in Vue..." without confirming if the project actually follows that pattern.
- If a relevant pattern is not found, **explicitly state it** rather than making one up.

## Security & Localization

- Always validate all user inputs.
- Do not trust client data.
- Always use authorization checks.
- Avoid raw SQL if ORM is available.
- Do not hardcode secrets, tokens, API keys, or passwords.
- Use environment variables for sensitive configurations.
- **Language:** Code comments and rules should be in English, but **UI text and error messages must remain in Indonesian** (e.g., "Anda tidak memiliki akses.").

## Testing Pattern

- **Always test the backend first** (e.g., verify services, controllers, database changes).
- **Only after backend testing is successful, proceed to test the frontend.**

## Role & Permission Rules

- **PermissionEnum:** Setiap penambahan permission baru wajib didaftarkan di `app/Enums/PermissionEnum.php`.
- **RoleEnum & Seeder:** Setelah mendaftarkan permission baru, Anda wajib mengaitkannya ke role yang sesuai di dalam file `database/seeders/Production/RolePermissionSeeder.php` dan `app/Enums/RoleEnum.php` (jika ada role baru).
- **Seeding:** Setiap ada pembaruan pada `PermissionEnum` atau `RolePermissionSeeder.php`, pastikan Anda selalu menjalankan ulang seeder menggunakan perintah `php artisan db:seed --class="Database\Seeders\Production\RolePermissionSeeder"` agar data permission di database ter-update.

## What AI Must Never Do

- Delete old migrations.
- Alter database schema without instruction.
- Alter existing permissions without instruction.
- Delete audit trails or soft deletes.
- Change UUIDs to auto increment.
- Make breaking changes without explanation.
- Use additional packages without approval.
- **Claim a function/column/route exists without verifying it in the code.**
- **Write code calling external APIs/services assuming response formats without checking documentation or existing code.**
- **State a task is "completed and tested" without actually running or showing how it was verified.**
- **Make silent assumptions regarding ambiguous requirements — must ask first.**

## Definition of Done

Before marking a task as complete, AI must ensure:

- Backend has been fully tested and verified.
- Frontend behavior runs properly (only tested after backend success).
- Vue build using `npm run build` succeeds without errors.
- All called functions/methods actually exist (verified, not assumed).
- No unused or invalid imports.
- Referenced migrations/models/routes have been checked for existence.
- Code has been re-read to ensure consistency with project patterns.
- If parts cannot be verified (e.g., no DB access), AI must explicitly state these open assumptions.

## Output Requirements

When producing code, AI must provide:

1. **Summary** — summary of changes made.
2. **Files Changed** — list of modified/created files.
3. **Reasoning** — logic behind the changes.
4. **Verification** — what was checked/verified before writing code.
5. **Potential Impact** — potential impact of changes.
6. **Testing Steps** — example testing steps if required.
7. **Open Assumptions** — unverified assumptions (if any).
