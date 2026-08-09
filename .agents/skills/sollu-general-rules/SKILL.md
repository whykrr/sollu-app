---
name: sollu-general-rules
description: General rules, verification steps, security, roles, permissions, and definition of done for the Sollu app project. Trigger this when starting a new task, reviewing code, or finishing up a task.
---

# Sollu General Rules

## 1. Tech Stack
- **Backend:** Laravel 11, PHP 8.3
- **Frontend:** Vue 3 (Composition API), Inertia.js 1.2, Tailwind CSS v4
- **Packages:** Pinia, Ziggy, Spatie Permission, FontAwesome 6

## 2. Before Coding & Verification
- **Read Completely:** Read target files, dependencies, traits, services, and migrations before editing. Do not guess signatures, column names, or route names.
- **Check Existence:** Verify `composer.json`/`package.json` for libraries and `routes/` for endpoints. State "needs verification" if unsure.
- **Preserve Scope:** Follow existing patterns with file path + class/method references. No major refactors, dependency additions, feature deletions, or SQL raw queries without instructions. Ask first if requirements are ambiguous.

## 3. Security, Roles & Localization
- **Security:** Validate all inputs, enforce authorization checks, use ORM, and keep secrets in `.env`.
- **Localization:** Code comments/rules in **English**; UI text & error messages strictly in **Indonesian** (e.g. `"Anda tidak memiliki akses."`).
- **Role & Permission Workflow:** Register new permissions in `app/Enums/PermissionEnum.php` and assign in `database/seeders/Production/RolePermissionSeeder.php` (and `RoleEnum.php` if applicable). Run: `php artisan db:seed --class="Database\Seeders\Production\RolePermissionSeeder"`.

## 4. Testing & Strict Prohibitions
- **Testing Order:** ALWAYS test backend first, then test frontend.
- **Forbidden:** Never delete old migrations, alter schema/permissions without instructions, delete audit/soft deletes, convert UUID to auto-increment, introduce breaking changes, or claim a task is complete/verified without running tests.

## 5. Definition of Done & Output Format
- **Criteria:** Backend tested, Frontend verified, `npm run build` succeeds without errors, all called functions exist, no unused imports, and open assumptions explicitly declared.
- **Response Format:**
  1. **Summary** (overview of changes)
  2. **Files Changed** (list of files)
  3. **Reasoning** (architectural/logic justification)
  4. **Verification** (what was checked before coding)
  5. **Potential Impact** (side-effects/risks)
  6. **Testing Steps** (instructions to test)
  7. **Open Assumptions** (unverified items, if any)

