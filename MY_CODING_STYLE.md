You are an experienced Laravel developer working on my project.

Read this style guide fully before writing any code — follow it exactly:

# MY_CODING_STYLE

This document is based on a full scan of the repository-tracked project files in this codebase. It is written as an implementation guide for an AI that must build new modules in the same style as the current project.

## 1. PROJECT OVERVIEW

### What this project is

This is a Laravel 13 application with a server-rendered Blade UI, localized Arabic/English content, role/permission management via Spatie, server-side DataTables via Yajra, and a Vuexy-based Bootstrap admin layout.

The currently active, clearly wired modules are:

- `Dashboard`
- `Users`
- `Users Archive`
- `Settings Center`
- Settings submodules such as:
  - `Roles`
  - `Client Status`
  - `Client Sections`
  - `Sectors`
  - `Marketing Channels`
  - `Countries`
  - `Cities`
  - `Content Types`
  - `Publishing Patterns`
  - `Content Purposes`
  - `Campaign Sections`
  - `Target Audiences`

There are also additional controllers/views/assets in the repo for other domains, but the strongest reusable style patterns for new work come from `Users` and the `Settings/*` modules.

### Tech stack used

- Backend: `PHP 8.4`, `Laravel 13`
- Auth/UI starter: `Laravel Breeze`
- Permissions: `spatie/laravel-permission`
- Activity logging: `spatie/laravel-activitylog`
- Localization: `mcamara/laravel-localization`
- Data tables: `yajra/laravel-datatables`
- Build tool: `Vite`
- UI framework: `Bootstrap 5.3.3` from `package.json`
- Admin theme layer: Vuexy asset structure under `resources/assets/vendor`
- JS style: mostly `jQuery` + plugin-driven page scripts, not SPA-style
- Form validation: `@form-validation/*`
- Alerts/modals/feedback:
  - `SweetAlert2`
  - `Toastr`
  - Bootstrap modals
- Selects: `Select2`
- Drag sorting in settings tables: `SortableJS`

### CSS structure

There are two CSS layers:

- Core/theme/vendor CSS from Vuexy under `resources/assets/vendor/scss`
- App/page CSS under:
  - `resources/assets/css/...`
  - `resources/css/...`

Important actual patterns:

- Global theme/layout CSS is loaded from `resources/views/layouts/sections/styles.blade.php`
- DataTable-specific styling lives in `resources/assets/css/common/dataTable.css`
- Toastr/SweetAlert appearance overrides live in `resources/assets/css/common/toastr.css`
- Roles page custom styling lives in `resources/assets/css/settings/roles/index.css`
- `body` font is forced to `"Zain", sans-serif`

### Folder structure and what goes where

- `app/Http/Controllers`
  Backend page/request handlers. Settings modules usually have one controller per module.
- `app/Http/Requests`
  Validation classes per domain.
- `app/Actions`
  Business actions for non-trivial CRUD, especially `Users` and `Roles`.
- `app/DataTables`
  Yajra DataTable classes for list pages.
- `app/Models`
  Eloquent models grouped by domain.
- `app/Policies`
  Authorization rules.
- `app/Services`
  Reusable service logic like translation handling.
- `resources/views`
  Blade pages, layouts, sections, shared components, and module partials.
- `resources/assets/js`
  Page-level and shared frontend behavior.
- `resources/assets/css`
  Page-level and common custom CSS.
- `resources/assets/vendor`
  Theme/vendor assets bundled through Vite.
- `resources/js`
  Minimal app bootstrap (`bootstrap.js`, `app.js`).
- `resources/css`
  Global custom CSS files.
- `routes/web.php`
  Main localized route entrypoint.
- `routes/web/*.php`
  Feature route groups like `users.php` and `settings.php`.
- `lang/ar`, `lang/en`
  Translation dictionaries for labels, buttons, validation, sidebar text.

## 2. FILE & FOLDER ORGANIZATION

### How files are separated

There are two main module patterns:

1. Full CRUD resource pages
   Example: `Users`
   Uses controller + request + action + DataTable + multiple Blade pages + page JS validation.

2. Inline settings modules
   Example: `settings/clients/client_status`, `settings/marketing/target-audience`
   Uses controller + request + DataTable + one index page with create/edit modals + shared JS manager.

### Naming conventions

- Controllers: singular, domain-based, suffixed with `Controller`
  Example: `UserController`, `SettingsClientStatusController`
- Requests: `StoreXRequest`, `UpdateXRequest`, or shared request
  Example: `StoreUserRequest`, `SettingStoreRequest`
- Actions: verb-first
  Example: `CreateUserAction`, `BulkRestoreUsersAction`
- DataTables: model/module name + `DataTable`
  Example: `UserDataTable`, `SettingsClientStatusDataTable`
- Views:
  - Resource modules: `resources/views/{module}/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
  - Settings modules: `resources/views/settings/.../{module}/index.blade.php` and `action.blade.php`
- JS files:
  - Shared utilities: `resources/assets/js/common/*.js`
  - Module files: `resources/assets/js/{domain}/{file}.js`
- CSS files:
  - Shared: `resources/assets/css/common/*.css`
  - Module-specific: `resources/assets/css/{domain}/...`

### Where to put new files

- New Blade pages: `resources/views/{module}` or `resources/views/settings/.../{module}`
- New JS page files: `resources/assets/js/{domain}/{module}.js`
- New CSS page files: `resources/assets/css/{domain}/{module}.css`
- Shared Blade components: `resources/views/components`
- Shared layout sections: `resources/views/layouts/sections`
- Shared JS utilities: `resources/assets/js/common`
- Shared backend helpers/services: `app/Services`, `app/Support`, `app/Helpers`

## 3. HTML STRUCTURE PATTERNS

### Typical page structure

A normal page:

1. `@extends('layouts.layoutMaster')`
2. `@section('title', ...)`
3. `@section('breadcrumb')`
4. `@section('vendor-style')`
5. `@section('vendor-script')`
6. Optional `@section('page-style')`
7. Optional `@section('page-script')`
8. `@section('content')`

The actual layout chain is:

- `layouts.layoutMaster`
- `layouts.commonMaster`
- `layouts.contentNavbarLayout`

Shared UI pieces are included from:

- Navbar: `resources/views/layouts/sections/navbar/navbar.blade.php`
- Sidebar: `resources/views/layouts/sections/menu/verticalMenu.blade.php`
- Footer: `resources/views/layouts/sections/footer/footer.blade.php`
- Global styles/scripts: `resources/views/layouts/sections/styles.blade.php`, `scriptsIncludes.blade.php`, `scripts.blade.php`

### Bootstrap usage and custom patterns

Common recurring classes:

- Layout wrappers: `container-fluid`, `container-xxl`, `content-wrapper`, `container-p-y`
- Cards: `card`, `card-body`, `card-header`, `border`, `h-100`
- Grid: `row g-3`, `row g-4`, `col-md-6`, `col-12`
- Stats: `avatar`, `avatar-initial`, `bg-label-*`
- Buttons: `btn btn-primary`, `btn btn-label-secondary`, `btn btn-sm text-secondary`
- Tables: `table table-striped table-bordered w-100`

### Modals

Settings modules use Bootstrap modals inline inside the index page:

- Create modal id: `#addSectionModal`
- Edit modal id: `#editSectionModal`
- Edit form id: `#editSettingForm`

Edit buttons pass state through `data-*` attributes, then JS fills the modal and swaps the form action URL.

### Tables, forms, and cards

- Listing pages use Yajra-rendered tables via `{!! $dataTable->table(...) !!}`
- Forms are server-rendered Blade forms
- User create/edit pages use a one-step `bs-stepper`
- Settings pages use a card + table + modal forms
- Settings center uses navigation cards instead of a table

## 4. JAVASCRIPT PATTERNS

### How JS files are organized

- Global app boot:
  - `resources/js/bootstrap.js`
  - `resources/js/app.js`
- Theme behavior:
  - `resources/assets/js/main.js`
  - `resources/assets/js/config.js`
- Shared utilities:
  - `resources/assets/js/common/delete-ajax.js`
  - `resources/assets/js/common/delete-group.js`
  - `resources/assets/js/common/dataTable-selectAll.js`
  - `resources/assets/js/common/toastr.js`
  - `resources/assets/js/common/translate/translate-fields.js`
- Page modules:
  - `resources/assets/js/users/user-form-validation.js`
  - `resources/assets/js/users/filter.js`
  - `resources/assets/js/settings/clients/settings_client_status.js`
  - `resources/assets/js/settings/common/sort_order.js`
  - `resources/assets/js/settings/roles/index.js`
  - `resources/assets/js/settings/roles/role-form-validation.js`

### Current JS style

- Mostly IIFE or jQuery-ready style
- Uses globals intentionally:
  - `window.translations`
  - `window.translationConfig`
  - `window.routes`
  - `settingsConfig`
  - `window.handleBulkDelete`
  - `window.toastr`
- DOM references often use cached jQuery variables like `$form`, `$modal`, `$table`

### API calls / AJAX / DOM manipulation

- AJAX is done with `$.ajax(...)` for CRUD-heavy pages
- `fetch(...)` is used for translation auto-fill
- DOM manipulation is direct, usually with jQuery
- Form submissions:
  - Full page POST for `Users` and `Roles`
  - AJAX modal submit for settings submodules

### Alerts / notifications / loading states

- Confirmation dialogs use `SweetAlert2`
- Success and error feedback use `toastr`
- Loading state patterns:
  - Disable submit button
  - Replace button text with spinner markup
  - `Swal.showLoading()` for destructive actions

### Reusable utility functions

- Bulk delete: `handleBulkDelete`
- Select-all logic: shared checkbox sync in `dataTable-selectAll.js`
- Translation toggle/auto-translate: `translate-fields.js`
- Sort persistence: `updateSortOrder`

### Variable and function naming

Backend naming:

- Constants for routes/views:
  - `protected const VIEW_PATH`
  - `protected const ROUTE_NAME`
- Method names are conventional CRUD: `index`, `store`, `update`, `destroy`
- Reusable behavior methods are verb-based: `toggleStatus`, `updateSortOrder`

Frontend naming:

- Classes use `SettingsManager`
- Methods are explicit and imperative:
  - `bindFormSubmit`
  - `bindEditButton`
  - `bindStatusToggle`
  - `fillEditForm`
  - `clearValidationErrors`

## 5. CSS / STYLING PATTERNS

### Custom CSS conventions on top of Bootstrap

- Bootstrap is the base
- Theme files provide the main visual system
- App CSS mostly tweaks widgets rather than rebuilding components

Real examples:

- DataTable toolbar/button tweaks in `resources/assets/css/common/dataTable.css`
- SweetAlert/Toastr theme overrides in `resources/assets/css/common/toastr.css`
- Roles permission accordion layout in `resources/assets/css/settings/roles/index.css`

### Class naming approach

The codebase uses pragmatic names, not strict BEM:

- Utility-ish custom classes:
  - `btn-export`
  - `btn-add`
  - `custom-checkbox`
  - `color-circle-hover`
  - `permissions-container`
  - `permissions-column`
  - `section-divider`

### Colors, spacing, typography

- Global custom primary CSS variable in layout:
  - `--primary-color: #198c8c;`
- Theme config JS primary color:
  - `#d5a047`
- Settings center cards also use inline per-card accent colors
- Typography:
  - Google fonts loaded: `Cairo`, `Tajawal`, `Zain`
  - Final body font forced to `Zain`
- Spacing is heavily Bootstrap-based:
  - `g-2`, `g-3`, `g-4`, `mb-3`, `mb-4`, `mt-2`

## 6. EXISTING MODULES — EXACT PATTERN

### Users

Backend structure:

- Routes: `routes/web/users.php`
- Controller: `app/Http/Controllers/Users/UserController.php`
- Requests:
  - `app/Http/Requests/Users/StoreUserRequest.php`
  - `app/Http/Requests/Users/UpdateUserRequest.php`
- Actions:
  - `CreateUserAction`
  - `UpdateUserAction`
  - delete/restore/bulk actions
- DataTables:
  - `app/DataTables/Users/UserDataTable.php`
  - `app/DataTables/Users/UserArchiveDataTable.php`
- Policy: `app/Policies/Users/UserPolicy.php`

Frontend structure:

- Index page: `resources/views/users/index.blade.php`
- Create/edit pages:
  - `resources/views/users/create.blade.php`
  - `resources/views/users/edit.blade.php`
- Shared form partials:
  - `resources/views/users/partials/form.blade.php`
  - `form-translations.blade.php`
  - `create-actions.blade.php`
  - `edit-actions.blade.php`
- Row actions:
  - `resources/views/users/action.blade.php`
  - `resources/views/users/archive/action.blade.php`
- JS:
  - `resources/assets/js/users/user-form-validation.js`
  - `resources/assets/js/users/filter.js`

How CRUD works:

- Index is server-side DataTable
- Create/edit are full-page forms
- Delete/archive restore operations are AJAX
- Bulk delete/restore/force delete are AJAX through DataTable buttons

Validation:

- Backend: FormRequest classes
- Frontend: `FormValidation` plugin in `user-form-validation.js`

API response style:

- AJAX actions usually return JSON with:
  - `status` + `message`
  - or `success` + `message`

### Permissions

Important reality of this repo:

- There is no standalone `Permissions` module page.
- The live permissions UI is effectively the `Roles` module under `settings.roles`.
- Permission records are seeded in `database/seeders/RolesAndPermissionsSeeder.php`.
- Permissions are grouped by `section` and assigned inside role create/edit pages.

Pattern:

- Index page: `resources/views/settings/roles/index.blade.php`
- Create/edit pages:
  - `resources/views/settings/roles/create.blade.php`
  - `resources/views/settings/roles/edit.blade.php`
- Controller: `app/Http/Controllers/Settings/Roles/RoleController.php`
- Actions:
  - `CreateRoleAction`
  - `UpdateRoleAction`
  - `DeleteRoleAction`
- JS:
  - `resources/assets/js/settings/roles/index.js`
  - `resources/assets/js/settings/roles/role-form-validation.js`
- CSS:
  - `resources/assets/css/settings/roles/index.css`

How it works:

- Index is card-based, not DataTable-based
- Create/edit use a one-step stepper form
- Permissions are shown as accordion groups by section
- “Select all” exists globally and per section

### Settings

The repeated settings submodule pattern is the cleanest reusable module scaffold in the project.

Representative files:

- Controller: `app/Http/Controllers/Settings/Clients/SettingsClientStatusController.php`
- DataTable: `app/DataTables/Settings/Clients/SettingsClientStatusDataTable.php`
- View: `resources/views/settings/clients/client_status/index.blade.php`
- Action partial: `resources/views/settings/clients/client_status/action.blade.php`
- Shared JS manager: `resources/assets/js/settings/clients/settings_client_status.js`
- Shared sorting JS: `resources/assets/js/settings/common/sort_order.js`
- Shared request: `app/Http/Requests/Settings/Shared/SettingStoreRequest.php`

This exact structure is reused with tiny substitutions for:

- `client_sections`
- `marketing_channels`
- `sectors`
- `countries`
- `cities`
- `content_type`
- `publish_pattern`
- `content_purpose`
- `content_section`
- `target-audience`

How CRUD works:

- List via DataTable
- Create in modal via AJAX POST
- Edit in modal via AJAX POST + `@method('PUT')`
- Delete via shared SweetAlert + AJAX delete
- Status toggle via AJAX `PATCH`
- Sort order via SortableJS + AJAX `POST`

## 7. THE RULES — HOW TO ADD A NEW MODULE

When adding a new module, follow one of the two existing real patterns.

### Pattern A: Full resource module like Users

1. Create route file entries in `routes/web/{domain}.php` or add to an existing group.
2. Create controller in `app/Http/Controllers/...`.
3. Create FormRequests in `app/Http/Requests/...`.
4. Create model in `app/Models/...`.
5. Create action classes in `app/Actions/...` if the module has non-trivial create/update/delete logic.
6. Create DataTable in `app/DataTables/...`.
7. Create views:
   - `index.blade.php`
   - `create.blade.php`
   - `edit.blade.php`
   - `show.blade.php`
   - `action.blade.php`
   - `partials/form.blade.php`
8. Create page JS in `resources/assets/js/{module}/...`.
9. Add sidebar entry in `resources/views/layouts/sections/menu/verticalMenu.blade.php`.
10. Add translation keys in `lang/ar/*` and `lang/en/*`.
11. Add permissions to seeder if the module needs auth control.

### Pattern B: Inline settings module like Client Status / Target Audience

1. Add route group under `routes/web/settings.php`.
2. Create controller with:
   - `index`
   - `store`
   - `update`
   - `destroy`
   - `toggleStatus`
   - `updateSortOrder`
3. Reuse `SettingStoreRequest` if fields are only translated name + color. Otherwise create a module-specific request.
4. Create model with `name_ar`, `name_en`, `color`, `status`, `sort_order`.
5. Create DataTable.
6. Create index view by copying an existing settings module page.
7. Create `action.blade.php` partial with edit/delete buttons.
8. Reuse:
   - `resources/assets/js/settings/clients/settings_client_status.js`
   - `resources/assets/js/settings/common/sort_order.js`
   unless the module needs special behavior.
9. Add a card link in `resources/views/settings/center/index.blade.php`.
10. Add sidebar/translation labels as needed.

### Exact page structure to follow

For list pages:

- `@extends('layouts.layoutMaster')`
- vendor DataTables/select2/sweetalert sections
- `@include('common.translations.translations')`
- optional `@include('common.translations.translate-fields')`
- page JS imports
- card statistics if needed
- `<x-alerts.validation-errors />`
- card wrapper containing `{!! $dataTable->table(...) !!}`
- inline Bootstrap modals if using settings pattern

### Exact JS structure to follow

- Put shared logic in `resources/assets/js/common`
- Put module JS in `resources/assets/js/{domain}/...`
- Use jQuery and plugin initialization
- Expose only necessary globals
- Use Toastr for messages and SweetAlert2 for confirmations
- For AJAX forms:
  - prevent default
  - disable submit
  - clear previous validation errors
  - submit serialized form
  - reload DataTable on success

### How to add to sidebar/navigation

- Add `<li class="menu-item ...">` in `resources/views/layouts/sections/menu/verticalMenu.blade.php`
- Use `Route::is(...)` for active/open state
- Use `@can(...)` / `@canany(...)` guards
- Use `__('sidebar....')` labels

### How to make API calls

- Prefer `$.ajax(...)` for module CRUD to match existing style
- Include CSRF token from `<meta name="csrf-token">`
- Return small JSON payloads with `success`/`status` and `message`

### How to show success/error feedback

- Success: `toastr.success(...)`
- Error: `toastr.error(...)`
- Confirm destructive actions with `Swal.fire(...)`
- Show loading inside alert for delete/restore/force-delete flows

### Other project-specific rules

- Use translatable inputs for user-facing names
- Respect Arabic/English current locale in labels and displayed columns
- Keep settings modules modal-based unless there is a strong reason to make a separate create/edit page
- Keep DataTables server-side and rendered through backend DataTable classes
- Keep route/view constants in controllers when following existing controller style

## 8. REAL CODE EXAMPLES

### Example: page skeleton from `resources/views/users/index.blade.php`

```blade
@extends('layouts.layoutMaster')

@section('title', __('sidebar.users_management'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', ...])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', ...])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-script')
    @include('common.translations.translations')
    @vite(['resources/assets/js/users/filter.js', ...])
@endsection

@section('content')
    @include('users.partials.statistic')
    <div class="card">
        <div class="card-body">
            @include('users.partials.filters')
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], true) !!}
        </div>
    </div>
@endsection
```

### Example: settings module AJAX scaffold from `resources/views/settings/clients/client_status/index.blade.php`

```blade
@section('page-script')
    @include('common.translations.translations')
    @include('common.translations.translate-fields')

    @vite(['resources/assets/js/settings/clients/settings_client_status.js', 'resources/assets/js/settings/common/sort_order.js', ...])

    <script>
        window.routes = {
            updateSortOrder: "{{ route('settings.client-status.update-sort-order') }}"
        };

        const settingsConfig = {
            updateRoute: '{{ route('settings.client-status.update', ':id') }}',
            toggleStatusRoute: '{{ route('settings.client-status.toggle-status', ':id') }}',
        };
    </script>
@endsection
```

### Example: controller constants and rendering pattern from `UserController`

```php
protected const VIEW_PATH  = 'users';
protected const ROUTE_NAME = 'users';

public function index(UserDataTable $dataTable, GetUserStatusAction $getUserStatusAction)
{
    $status = $getUserStatusAction->execute();
    return $dataTable->render(self::VIEW_PATH . '.index', ['status' => $status]);
}
```

### Example: shared translated input from `resources/views/components/form/translatable-input.blade.php`

```blade
<div class="translation-field" data-translation-container data-source-locale="{{ $locale }}"
    data-target-locale="{{ $locale === 'ar' ? 'en' : 'ar' }}">
    ...
    <button type="button" class="btn btn-sm btn-outline-secondary" data-translation-toggle>
        {{ $secondaryVisible ? __('common.hide_translation') : __('common.show_translation') }}
    </button>
    ...
</div>
```

### Example: shared DataTable select-all behavior from `resources/assets/js/common/dataTable-selectAll.js`

```js
$(document).on('change', '[id^="select-all"]', function () {
    var table = $(this).closest('.dataTables_wrapper').find('table');
    var isChecked = this.checked;
    table.find('tbody input.row-checkbox').prop('checked', isChecked);
});
```

### Example: shared delete flow from `resources/assets/js/common/delete-ajax.js`

```js
Swal.fire({ ... }).then(result => {
    if (!result.isConfirmed) return;

    $.ajax({
        url: route,
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            _method: 'DELETE',
        },
        success: function (response) {
            toastr.success(response.message);
            const table = $('.dataTable').DataTable();
            table.ajax.reload(null, false);
        }
    });
});
```

### Example: DataTable button pattern from `UserDataTable`

```php
return [
    'text'      => '<i class="fas fa-plus-circle me-1"></i>' . __('users.create_title'),
    'className' => 'btn btn-primary btn-add',
    'action'    => "function() { window.location.href='" . route(self::ROUTE_NAME . '.create') . "'; } ",
];
```

### Example: CSS pattern from `resources/assets/css/common/dataTable.css`

```css
.btn-export {
    background-color: white !important;
    color: black !important;
    border: none !important;
}

.custom-checkbox input[type="checkbox"] {
    border: 2px solid #198c8c;
}
```

## Final implementation guidance

If you want output identical to the current codebase, default to these rules:

- For admin lists, use Blade + Yajra DataTable, not a custom JS-rendered table.
- For lightweight settings entities, use one index page with create/edit modals.
- For heavier entities like users, use dedicated create/edit pages with a stepper form.
- Use jQuery plugins and imperative JS instead of introducing a new frontend framework.
- Keep localization and translated inputs built into the form shape from day one.
- Match the existing route naming, controller constants, view folder naming, and DataTable-driven listing flow exactly.

### IMPORTANT NOTE ON SETTINGS MODULES

The settings submodules currently in the project (Client Status, Sectors, Countries, etc.)
were built as EXAMPLES to establish the pattern — not because they are all required.

Rules for adding new settings modules:

1. If any future module needs a settings/lookup table, build it using the same Pattern B structure.

2. The base fields (name_ar, name_en, color, status, sort_order) are just the common case.
   If a new settings module needs extra fields, ADD them — do not remove the base ones unless they are truly irrelevant.
   Examples:
   - A "Countries" module might need: code, phone_code, flag
   - A "Cities" module might need: country_id (foreign key)
   - A "Payment Methods" module might need: icon, fees_percentage
   - A "Document Types" module might need: max_size_mb, allowed_extensions

3. The pattern stays the same regardless of extra fields:
   - Add extra fields to the migration
   - Add extra validation in the Request class
   - Add extra inputs in the modal form
   - Pass extra data in the DataTable columns
   - Everything else (AJAX flow, toggle status, sort order, modals) stays identical

4. Never hardcode settings values in the code. If something is configurable, it belongs in a settings module.
