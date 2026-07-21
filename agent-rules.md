# AI Development Rules

## Tech Stack Reference

- **Backend:** Laravel 11, PHP 8.3
- **Frontend:** Vue 3 (Composition API), Inertia.js 1.2, Tailwind CSS v4
- **Packages:** Pinia, Ziggy, Spatie Permission, FontAwesome 6

## General Rules

### Before Coding

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

### Verification Rules

- Before calling existing methods/functions/traits, **you must read their definitions first**. Do not assume signatures from names.
- Before using database columns, **you must check the migration or related model**. Do not assume column names (e.g., `user_id` vs `created_by`).
- Before calling routes, **you must check `routes/web.php` or `routes/api.php`**. Do not invent route names.
- Before using packages/libraries, **you must check `composer.json` or `package.json`** for installation and versions. Do not assume package availability.
- If unsure if a file/function/column exists, explicitly state "needs verification" and read the related file — do not guess and proceed.
- Do not create API response examples, env variable names, or config values that are not confirmed to exist in the project (e.g., `.env.example`, `config/*.php`).

### Referencing Existing Code

- When mentioning "there is an existing pattern in the project", you **must include the file path and function/class name**.
- Do not say "usually in Laravel..." or "generally in Vue..." without confirming if the project actually follows that pattern.
- If a relevant pattern is not found, **explicitly state it** rather than making one up.

### Security & Localization

- Always validate all user inputs.
- Do not trust client data.
- Always use authorization checks.
- Avoid raw SQL if ORM is available.
- Do not hardcode secrets, tokens, API keys, or passwords.
- Use environment variables for sensitive configurations.
- **Language:** Code comments and rules should be in English, but **UI text and error messages must remain in Indonesian** (e.g., "Anda tidak memiliki akses.").

### Testing Pattern

- **Always test the backend first** (e.g., verify services, controllers, database changes).
- **Only after backend testing is successful, proceed to test the frontend.**

---

## Backend Rules (Laravel)

### Architecture & Controllers

Follow this structure:

```text
Controller
    ↓
Action / Service
    ↓
Repository (optional)
    ↓
Model
```

**Controller Pattern:**
Use a **hybrid** approach:

1. **Resource-style (inline):** Use for simple CRUD operations without complex side-effects. Logic can be written directly in the controller.
2. **Service-injected:** For complex business logic, use a _thin controller_. Controllers should only handle request validation, authorization, and responses. Inject Service classes via constructor (e.g., `CreateOutletService`).

**Do not hardcode authorization:**

```php
// Wrong
if ($user->role == 'admin')
```

Use Gates, Policies, Spatie Permissions, or inside Form Requests.

### Model Standards

**Property/Method Ordering:**

1. `use` Traits (one per line)
2. `$fillable`
3. `$hidden` (if exists)
4. `$sortable` (if exists)
5. `$appends` (if exists)
6. `casts(): array`
7. Custom notification methods (if exists)
8. Relationships (BelongsTo → HasMany → BelongsToMany → HasOne)
9. `scopeFilters()`
10. Other scopes
11. Custom methods/helpers

**Trait Style:**

- Each trait must be on a separate line for consistency.

```php
use HasRoles;
use HasFactory;
use HasUuids;
```

**`casts()` Pattern:**

- Use the method style (Laravel 11 standard).
- Use column-aligned `=>` arrows for readability.

```php
protected function casts(): array
{
    return [
        'is_active' => 'boolean',
    ];
}
```

**PHPDoc Annotations:**

- Include relationship annotations using PHPDoc (e.g., `/** @property-read Collection|Outlet[] $outlets */`).

**Relationship Patterns:**

- Always specify return types: `public function business(): BelongsTo`.

### Database & Migration

- All foreign keys must use constraints.
- Use UUID or Auto Increment for primary keys (UUID is standard, via `HasUuids`).
- Always add indexes on foreign keys, code, sku, slug, and searchable columns.
- > **Verification:** Before adding new columns, check existing migrations for the same table to avoid duplicate columns or naming conflicts.

### Query Patterns

- Use model scopes (`scopeFilters`) with `->when()` to organize query conditions.
- Prioritize Eloquent, avoid `DB::select(...)` unless necessary.
- Avoid N+1 queries. Always consider eager loading (`->with()`).

### Service Layer

For complex operations, business logic must reside in a Service.

- **Naming:** Action-oriented (e.g., `CreateOrderService`, `UpdateOutletService`).
- **Structure:** Placed in specific subdirectories based on domain (e.g., `app/Services/Outlet/`).
- **Main method:** `execute(array $data, User $user)` (or similar parameters).
- **Integrity:** Always wrap actions in `DB::transaction()`.
- **Audit Logging:** Every mutation must create an audit log (e.g., using `OutletAuditLog` or `AuditLogService`).

### Form Request

**Naming Convention:**

- `Get{Entity}Request` for index/list.
- `Store{Entity}Request` for creation.
- `Update{Entity}Request` for updates.

**Base Class:**

- Extend `BaseInertiaFormRequest` if authorization failure should redirect back with an Indonesian error message ("Anda tidak memiliki akses.").

**Authorization (`authorize()`):**

- Should return permission string checks (e.g., `return Auth::user()?->can('outlet.create');`).

**Rules (`rules()`):**

- **Column-Aligned:** Format rules in the array using aligned `=>` arrows for readability.
- Use the array format for complex rules or Store/Update requests: `['required', 'string', 'max:255']`.
- All form validation must go through Form Requests, not `$request->validate()` in the controller.

### API & Route

- Group routes using `prefix()`, `name()`, and `group()`.
- Route names must follow dot-notation (`entity.action`, e.g., `settings.outlets.index`).
- **Standard Endpoint Naming:**
    - `DELETE /{model}` → `delete` (soft delete)
    - `PUT /{model}/restore` → `restore` (using `->withTrashed()`)
    - `DELETE /{model}/destroy` → `destroy` (force delete)

### CSV Import/Export Pattern

- **Background Processing:** Both Import and Export of potentially large CSV files must be processed in the background using Queued Jobs.
- **Base Abstract Jobs:** Extend `AbstractCsvExportJob` and `AbstractCsvImportJob` (located in `app/Jobs/`) to ensure a consistent approach to chunking data and writing/reading rows.
- **Import Error Handling:** Validation errors during import must NOT cause the entire job to fail. Instead, collect the failed rows, generate a new CSV containing these rows with an added "Error Message" column, and store it.
- **Notifications:** Use Laravel Notifications (`CsvExportCompleted`, `CsvImportCompleted`) to inform the user. The Import notification must include a download link to the failed rows CSV if any validation errors occurred.
- **Template Generation:** Import templates should include a dummy row as an example for users. Use case-insensitive matching for relationships (like UOM name) to improve UX.

### Logging & Seeder

- **Logging:** Log only Errors, Integration failures, Payment failures, and Critical events. Do not spam logs.
- **Seeder:** Must be idempotent. Use `updateOrCreate()` or `firstOrCreate()`. Avoid `create()` for master data.

---

## Frontend Rules (Vue 3 / Inertia)

### Component Structure

Use **Composition API** (`<script setup>`).

**Template vs Script Ordering:**

- **Always use template-first ordering:**

```vue
<template>...</template>
<script setup>
...
</script>
```

**Import Ordering:**

```javascript
// 1. Vue core
import { ref, computed, watch, onMounted } from 'vue';
// 2. Inertia / Router
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
// 3. Third-party libraries
import { debounce } from 'lodash';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus, faPencil } from '@fortawesome/free-solid-svg-icons';
// 4. Global Components (alias @/)
import Container from '@/Components/UI/Container.vue';
import Table from '@/Components/Tables/Table.vue';
// 5. Composables
import { formatDateTimeSimple } from '@/Composable/date';
// 6. Stores (Pinia)
import { useModalStore } from '@/store/notification';
// 7. Local/Page Components (relative path)
import Filter from './Components/Filter.vue';
import Form from './Components/Form.vue';
```

**Script Setup Internal Ordering:**

```javascript
// 1. defineOptions (if any)
// 2. defineProps
// 3. defineEmits
// 4. Store / Composable initialization
// 5. Reactive state (ref, reactive)
// 6. Computed properties
// 7. Methods / Functions
// 8. Watchers
// 9. Lifecycle hooks (onMounted, etc.)
```

**Props Declaration Style:**

- **Pattern A:** `defineProps({ user: Object })` (if props are NOT accessed in `<script>`)
- **Pattern B:** `const props = defineProps({ user: Object })` (if props ARE accessed in `<script>`)
- **Do not use destructuring** for props to maintain Vue 3 reactivity.

### Page Architecture

- One component, one responsibility. Keep components under 500 lines.
- **Page Folders:** Place sub-components inside `Pages/{Module}/Components/` (e.g., `Filter.vue`, `Form.vue`, `Detail.vue`).
- **Tabs:** Place tab components inside `Pages/{Module}/Tabs/` (e.g., `GeneralTab.vue`, `SettingsTab.vue`).

### UI Consistency & Page Patterns

**Inertia Workflow (Index + Show):**

- The `index` and `show` routes often map to the same controller method.
- The `show` route fetches detail data via partial reload (`only: ['user']`).
- When closing a detail view, use `router.get` to reset the URL and clear the prop.

**Standard List/Index Pattern:**
All index pages must use `<Container>` (`@/Components/UI/Container.vue`):

- `<template #header>`: Contains `<ContainerHeader>` `<Filter>` component and action buttons (Add).
- **Default Slot**: Contains `<Table>` component with props `headers`, `data`, `sort`, `sort-direction`.
- `<template #footer>`: Contains `<Pagination>`.

**Data Fetching & Inertia Routing:**

- Form submissions (`form.post()`, `form.put()`) must always include `preserveState: true` and `preserveScroll: true`.
- Transitioning or loading base details uses `router.visit()` with **partial reloads** (`only: ['prop_name']`).
- For complex data loading in tabs, use API calls (Axios) to prevent page freeze. Register routes in `web.php`.

**Filter Pattern:**

- Use `reactive()` for state (not `useForm`).
- Use `watch` with `debounce` (e.g., 500ms) for auto-submit.
- When filter changes, always reset pagination to `page: 1`.
- Merge parameters: `router.get(route('...'), { ...route().params, ...filterForm, page: 1 }, { preserveState: true, preserveScroll: true })`.

**Modal Form Lifecycle Pattern:**

- Initialize `useForm` with null/empty default values.
- Watch the data prop (`props.user`) with `{ immediate: true }`.
- Inside the watcher, call `form.reset()` and then populate `form.field = data.field`.
- On submit success (`onSuccess` callback), call `form.reset()` and emit `close`.

**PopUpPage vs Modal Pattern:**

- `PopUpPage.vue` (Side Panel): For edit/create forms or large entity details. Visibility is controlled via `:class="{ show: show }"`.
- `Modal.vue` / `ModalDelete.vue` (Centered): Only for confirmation warnings or quick actions.

**Tab Pattern:**

- For complex details, wrap a `<Tab>` component inside a `PopUpPage`.
- Define tabs as a computed array: `[{ label: 'General', icon: faCog, page: GeneralTab, props: { outlet } }]`.

**Form Fields Pattern:**

- Use custom components (e.g., `TextField`, `DropdownField`).
- Init state with Inertia's `useForm`.
- Validation error display:

```html
<TextField
    id="name"
    v-model="form.name"
    label="Nama Lengkap"
    :class="{ 'is-invalid': form.errors.name }"
    :feedback="form.errors.name"
/>
```

---

## CSS & Styling Rules

### Tailwind v4 & Custom Classes

- The project uses **Tailwind CSS v4** (`@theme`, `@utility`).
- **Color Tokens:** `--color-main`, `--color-secondary`, `--color-danger`, `--color-success`, `--color-warning`, `--color-info`.
- Use a **hybrid approach**: Use custom classes for standard UI elements and Tailwind utilities for layouts and spacing.
- Extract long Tailwind classes into custom classes in `resources/css/app.css` if they become too verbose.
- Create reusable custom classes for redundant Tailwind class combinations in `resources/css/app.css`.
- Use spacing scale 4 (`gap-4`) for normal elements, scale 3 for main area padding, scale 2 for tight components & gap form.
- do not use spacing scale inside `PopUpPage` body

### Custom Class Inventory

- **Buttons:** `btn`, `btn-main`, `btn-success`, `btn-danger`, `btn-outline-main`, `btn-sm`, `btn-xs`
- **Forms:** `form`, `form-group`, `form-check`, `form-check-input`, `form-feedback`, `is-invalid`
- **Cards:** `card`, `card-header`, `card-outline`
- **Modals:** `modal`, `modal-dialog`, `modal-content`, `modal-header`, `modal-body`, `modal-footer`
- **Tables:** `table`, `table-hovered`
- **Badges:** `badge`, `badge-success`, `badge-danger`, `pill`
- **Layouts:** `sidebar`, `nav-item`, `nav-dropdown`, `tab`, `floating-scroll`

---

## Component Reference

### UI Components (`@/Components/UI/`)

- **Container.vue**: Main wrapper (`#header`, default, `#footer`, `#widgets`).
- **PopUpPage.vue**: Side panel modal (`title`, `sub-title`, `size`, `#footer`).
- **Tab.vue**: Tabbed navigation (`pages`, `vertical`).
- **FilterSearch.vue**: Search input bound via `v-model`.

### Form Components (`@/Components/Form/`)

- `TextField.vue`, `EmailField.vue`, `PasswordField.vue`, `NumberField.vue`, `TextareaField.vue`, `DropdownField.vue`, `CheckboxField.vue`, `RadioField.vue`, `Switch.vue`, `QuillEditor.vue`.
- These use `defineOptions({ inheritAttrs: false })` and inherit attributes via `v-bind="$attrs"`. They emit `update:modelValue`.

### Tables Components (`@/Components/Tables/`)

- **Table.vue**: Data table (`headers`, `data`, `sort`, `sortDirection`, `action`). Custom slots supported via `col.slot`.
- terdapat properti actions, gunakan true jika terdapat button actions, dan ubah custom template pada #actions (jangan gunakan kolom action pada setting table nya)
- **Pagination.vue**: Paginator (`links`, `from`, `to`, `total`, `perPage`). Uses Inertia `<Link>`.

---

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

---

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

---

## Output Requirements

When producing code, AI must provide:

1. **Summary** — summary of changes made.
2. **Files Changed** — list of modified/created files.
3. **Reasoning** — logic behind the changes.
4. **Verification** — what was checked/verified before writing code.
5. **Potential Impact** — potential impact of changes.
6. **Testing Steps** — example testing steps if required.
7. **Open Assumptions** — unverified assumptions (if any).
