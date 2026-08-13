---
name: sollu-code-quality
description: >-
  Code quality enforcement, formatting standards using Laravel Pint (vendor/bin/pint) and ESLint (npm run fix:eslint),
  verification procedures, strict development prohibitions, testing order, and Definition of Done for Sollu App.
  MUST trigger before finalizing deliverables, running code reviews, formatting files, or completing tasks.
---

# Sollu Code Quality & Verification Standards

Standar kualitas kode, pengujian, pembersihan otomatis (*linter*), serta kriteria *Definition of Done* untuk aplikasi Sollu App.

## 1. Automated Code Formatting & Linting

Sebelum menyelesaikan tugas atau mengirimkan perubahan kode:
- **Backend (PHP/Laravel):** Jalankan Laravel Pint untuk memastikan format sesuai standar PSR-12 & Laravel style:
  ```bash
  vendor/bin/pint
  ```
- **Frontend (Vue/JS):** Jalankan ESLint untuk merapikan file `.vue` dan `.js`:
  ```bash
  npm run fix:eslint
  ```
- **Frontend Build Test:** Pastikan kompilasi bundler Vite berhasil tanpa error:
  ```bash
  npm run build
  ```

## 2. Testing Sequence & Verification Workflow

- **Testing Order:** ALWAYS test backend endpoints/unit logic FIRST, then proceed to test frontend UI components.
- **Pre-Code Inspection:** Read target files, dependencies, traits, services, and migrations completely before editing. Do NOT guess signatures or database column names.
- **Runtime Verification:** NEVER declare a task complete without running build/test commands and verifying the actual execution results.

## 3. Strict Prohibitions (Forbidden Actions)

1. **NO Monolithic Refactoring:** Do NOT perform large-scale refactoring or delete existing features outside the scope of current requirements.
2. **NO Destructive Migration Alters:** NEVER delete old migrations or alter production schema/permissions without explicit instructions.
3. **NO Soft Delete / Audit Log Removal:** NEVER delete audit log records (`AuditLogService`) or bypass soft-delete traits (`SoftDeletes`).
4. **NO Raw SQL Injection Risks:** Never bypass Eloquent with raw SQL string concatenation.
5. **NO Direct Role Hardcoding:** NEVER write `$user->role == 'admin'`. Always use Spatie permission checks (`$user->can('permission.name')`).
6. **NO Hardcoded Controller Messages:** NEVER write manual hardcoded strings for controller response messages (e.g. `->with('success', 'Data berhasil dibuat')`). ALWAYS use `App\Constants\*` (`ResourceMessage`, `AuthorizationMessage`, `FlashDataVariable`) or Laravel language translation files (`__('messages.key')`).

## 4. Definition of Done (DoD) Checklist

A feature or bugfix is considered **DONE** only when:
- [ ] Backend logic & endpoints tested and returning accurate HTTP status codes.
- [ ] Controller response messages use `App\Constants\*` (`ResourceMessage`, `FlashDataVariable`) or `lang/` translation files without any hardcoded strings.
- [ ] Frontend UI verified visually and functionally via `browsermcp` (navigasi URL, screenshot, snapshot, console log check), and layout aligns with design standards.
- [ ] Code formatted with `vendor/bin/pint` and `npm run fix:eslint`.
- [ ] `npm run build` executes cleanly with zero syntax or bundling errors.
- [ ] All permissions registered in `PermissionEnum.php` & `RolePermissionSeeder.php` (if applicable).
- [ ] Open assumptions or unverified items are explicitly documented to the user.
