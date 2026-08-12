---
name: sollu-roles-permissions
description: >-
  Role-Based Access Control (RBAC), Spatie Permission management, PermissionEnum, RoleEnum, RolePermissionSeeder,
  authorization checks ($this->authorize), BaseInertiaFormRequest authorization, and useAuth composables in Sollu App.
  MUST trigger whenever creating or modifying permissions, roles, access controls, or authorization checks.
---

# Sollu Roles & Permissions Standard (RBAC)

Aturan pengelolaan Hak Akses (Role-Based Access Control / RBAC) menggunakan package `spatie/laravel-permission` pada Sollu App.

## 1. Permission Registration Workflow

Setiap kali fitur baru memerlukan otorisasi atau hak akses baru:
1. **Daftarkan Key Permission** di Enum `app/Enums/PermissionEnum.php` (Gunakan dot-notation, e.g. `SETTINGS_OUTLET_INDEX = 'settings.outlets.index'`).
2. **Assign ke Role** di `database/seeders/Production/RolePermissionSeeder.php` (dan `RoleEnum.php` jika melibatkan role baru).
3. **Jalankan Artisan Seeder:**
   ```bash
   php artisan db:seed --class="Database\Seeders\Production\RolePermissionSeeder"
   ```

## 2. Backend Authorization Rules

- **Controllers:** Wajib menggunakan `$this->authorize('permission.name')` atau `Illuminate\Support\Facades\Gate::authorize('permission.name')` di dalam method controller.
- **DILARANG** menggunakan `$this->middleware('permission:...')` di dalam constructor `__construct()` controller.
- **Form Requests:** Wajib mengembalikan boolean check permission pada method `authorize()` di class turunan `App\Http\Requests\BaseInertiaFormRequest`.
- **DILARANG HARDCODE ROLE:** Dilarang mengecek `$user->role === 'admin'` secara langsung. Selalu cek permission via `$user->can('permission.name')` atau `$user->hasPermissionTo(...)`.

## 3. Frontend Authorization (`useAuth`)

Di sisi Vue 3 (Composition API), otorisasi dilakukan menggunakan composable `@/Composable/useAuth`:

```js
import { useAuth } from '@/Composable/useAuth';

const { can, canAny, canAll, hasRole, isOwner } = useAuth();

// Contoh penggunaan di template atau script:
if (can('settings.outlets.create')) {
    // izinkan aksi
}
```

- **DILARANG** mengakses `usePage().props.auth` secara langsung jika helper `useAuth()` tersedia.
