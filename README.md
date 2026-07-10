# Custodian - Modern Role & Permission Management for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/amdadulhaq/custodian-laravel.svg?style=flat-square)](https://packagist.org/packages/amdadulhaq/custodian-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/amdad121/custodian-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/amdad121/custodian-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/amdad121/custodian-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/amdad121/custodian-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/amdadulhaq/custodian-laravel.svg?style=flat-square)](https://packagist.org/packages/amdadulhaq/custodian-laravel)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11%2F12%2F13-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Sponsor](https://img.shields.io/badge/Sponsor-%E2%9D%A4-pink?style=flat-square&logo=github)](https://github.com/sponsors/amdad121)

> A powerful, flexible, and developer-friendly role and permission management system for Laravel applications.

## Quick Start

Get up and running in 5 minutes:

> **Upgrading from an older version?** Check the [Upgrade Guide](UPGRADE.md) for detailed migration instructions.

### 1. Install via Composer

```bash
composer require amdadulhaq/custodian-laravel
```

### 2. Publish and run migrations

```bash
php artisan vendor:publish --tag="custodian-migrations"
php artisan migrate
```

### 3. Setup your User model

```php
<?php

namespace App\Models;

use AmdadulHaq\Custodian\Contracts\Roleable as RoleableContract;
use AmdadulHaq\Custodian\Concerns\Roleable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements RoleableContract
{
    use Roleable;
}
```

### 4. Create your first role and permission

```bash
php artisan custodian:create-role admin Administrator
php artisan custodian:create-permission users.create "Create Users"
```

### 5. Protect your routes

```php
Route::middleware('role:admin')->get('/admin', [AdminController::class, 'index']);
```

## Features

- **Modern PHP & Laravel** - Built for PHP 8.2+ and Laravel 11/12/13
- **Flexible Permission System** - Users can have permissions via roles
- **Wildcard Permissions** - Use `posts.*` to match all post-related permissions
- **Real-Time Gate Integration** - A single `Gate::before` hook resolves permissions and roles live; native `@can`, `@canany`, `@cannot` support with no stale definitions
- **Middleware Protection** - `role`, `permission`, and `role_or_permission` middleware
- **Blade Directives** - `@role`, `@hasrole`, `@hasanyrole`, `@hasallroles`
- **Type-Safe Enums** - IDE-friendly `PermissionType` enum
- **Protected Roles** - Protected roles cannot be deleted; attempts throw `ProtectedRoleException`
- **Permission Groups** - Organize permissions by resource
- **Interactive Commands** - Laravel Prompts for creating roles/permissions
- **Clean Architecture** - Separated concerns with traits and contracts
- **Developer Tools** - Pint, Pest, Rector, and Larastan included

## Support & Sponsorship

Building and maintaining high-quality open-source packages takes hundreds of hours of dedicated time. If you use Custodian in your commercial applications or it saves you significant development time, please consider supporting the project.

> **[Sponsor the Project](https://github.com/sponsors/amdad121)**
> Ensure the package stays actively maintained, receives rapid bug fixes, and continuous feature updates by becoming a monthly sponsor.

## Table of Contents

- [Installation](#installation)
- [Upgrade Guide](UPGRADE.md)
- [Configuration](#configuration)
- [Usage](#usage)
    - [User Setup](#user-setup)
    - [Creating Roles](#creating-roles)
    - [Creating Permissions](#creating-permissions)
    - [Wildcard Permissions](#wildcard-permissions)
    - [Role Management](#role-management)
    - [Permission Management](#permission-management)
    - [Checking Access](#checking-access)
    - [Middleware](#middleware)
    - [Gate Integration](#gate-integration)
    - [Blade Directives](#blade-directives)
    - [Artisan Commands](#artisan-commands)
    - [Query Scopes](#query-scopes)
- [Models Reference](#models-reference)
- [Exceptions](#exceptions)
- [Events](#events)
- [Performance](#performance)
- [Database Structure](#database-structure)
- [Enums](#enums)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

## Installation

### Requirements

- **PHP**: 8.2, 8.3, 8.4, or 8.5
- **Laravel**: 11.x, 12.x, or 13.x
- **Database**: MySQL 5.7+, PostgreSQL 9.6+, SQLite 3.8+, or SQL Server 2017+

### Step 1: Install via Composer

```bash
composer require amdadulhaq/custodian-laravel
```

### Step 2: Publish and Run Migrations

```bash
php artisan vendor:publish --tag="custodian-migrations"
php artisan migrate
```

This creates 4 tables:

- `roles` - Role definitions
- `permissions` - Permission definitions
- `permission_role` - Role-permission relationships
- `role_user` - User-role relationships
Pivot table names are derived from model table names; the defaults shown above are used unless you customize model tables.

### Step 3: Configure User Model

```php
<?php

namespace App\Models;

use AmdadulHaq\Custodian\Contracts\Roleable as RoleableContract;
use AmdadulHaq\Custodian\Concerns\Roleable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements RoleableContract
{
    use Roleable;
}
```

### Step 4: (Optional) Publish Config

```bash
php artisan vendor:publish --tag="custodian-config"
```

## Configuration

The `config/custodian.php` file:

```php
return [
    'models' => [
        'user' => \App\Models\User::class,
        'role' => \AmdadulHaq\Custodian\Models\Role::class,
        'permission' => \AmdadulHaq\Custodian\Models\Permission::class,
    ],
    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
    ],
    'middleware' => [
        'role' => 'role',
        'permission' => 'permission',
        'role_or_permission' => 'role_or_permission',
    ],
    'wildcard' => [
        'enabled' => env('CUSTODIAN_WILDCARD_ENABLED', true),
    ],
];
```

### Custom Models

To extend or replace the default models, point the config at your own classes — all relations, commands, and gate checks resolve them from the config:

```php
// config/custodian.php
'models' => [
    'user' => \App\Models\User::class,
    'role' => \App\Models\Role::class,        // extends AmdadulHaq\Custodian\Models\Role
    'permission' => \App\Models\Permission::class, // extends AmdadulHaq\Custodian\Models\Permission
],
```

Pivot table names are derived automatically from the models' table names.

## Usage

### User Setup

Add the `Roleable` contract and trait to your user model:

```php
use AmdadulHaq\Custodian\Contracts\Roleable as RoleableContract;
use AmdadulHaq\Custodian\Concerns\Roleable;

class User extends Authenticatable implements RoleableContract
{
    use Roleable;
}
```

Notes:

- `Roleable` trait on the user model handles both role and permission checks.
- Users do not receive permissions directly.
- Assign permissions to roles, then users inherit them from those roles.

### Creating Roles

```php
use AmdadulHaq\Custodian\Models\Role;

// Set fields on create (or later via update())
$role = Role::create([
    'name' => 'administrator',        // required, unique — used by all checks
    'label' => 'Administrator',       // optional display name
    'description' => 'Full system access',
    'is_protected' => true,           // protect from deletion
]);

$role->update(['label' => 'Admin']);

// Get fields
$role->getName();              // 'administrator'
$role->getLabel();             // 'Administrator'
$role->getDescription();       // 'Full system access'
$role->isProtectedRole();      // true — deleting now throws ProtectedRoleException

// Other role methods
$role->getPermissionNames();   // All permission names assigned to the role
$role->users;                  // Users with this role

// Query scopes
Role::protected()->get();      // Only protected roles
Role::unprotected()->get();    // Only unprotected roles
```

Roles can also be created via the CLI — see [Artisan Commands](#artisan-commands).

### Creating Permissions

```php
use AmdadulHaq\Custodian\Models\Permission;

// Set fields on create — only 'name' is required; the rest is display metadata
$permission = Permission::create([
    'name' => 'users.delete',         // required, unique — used by all checks
    'label' => 'Delete Users',        // optional display name
    'description' => 'Permanently remove user accounts',
    'group' => 'users',               // optional stored grouping
]);

// Wildcard permission — is_wildcard is set automatically when name ends with '*'
Permission::create([
    'name' => 'posts.*',
    'label' => 'Manage All Posts',
    'group' => 'posts',
]);

// Get fields
$permission->getName();         // 'users.delete'
$permission->getLabel();        // 'Delete Users'
$permission->getDescription();  // 'Permanently remove user accounts'
$permission->getGroup();        // 'users' (derived from the name prefix)
$permission->isWildcard();      // false
$permission->getType();         // PermissionType::DELETE (from the last name segment)
$permission->roles;             // Roles with this permission

// Query scopes — group permissions for an admin UI
Permission::wildcard()->get();           // Only wildcard permissions
Permission::byGroup('users')->get();     // All users.* permissions
Permission::all()->groupBy->getGroup();  // ['users' => [...], 'posts' => [...]]
```

Authorization only ever checks `name` — `label`, `description`, and `group` are display metadata for building admin UIs.

Note that `getGroup()` and `byGroup()` derive the group from the permission **name prefix** (`users` from `users.create`), not the stored `group` column, so the `resource.action` naming convention gives you grouping for free; the column is available for your own custom queries.

Permissions can also be created via the CLI — see [Artisan Commands](#artisan-commands).

### Wildcard Permissions

Wildcard permissions automatically match all sub-permissions:

```php
// Create wildcard permission
Permission::create(['name' => 'posts.*']);

// Assign to role
$role->givePermissionTo('posts.*');

// Now user can do all of these:
$user->hasPermission('posts.create');  // true
$user->hasPermission('posts.update');  // true
$user->hasPermission('posts.delete');  // true
$user->hasPermission('posts.publish'); // true
```

The `is_wildcard` boolean is automatically set when the name ends with `*`.

A permission named just `*` matches **every** permission — a super-admin grant. Wildcards can be disabled entirely via `CUSTODIAN_WILDCARD_ENABLED=false`.

### Role Management

**Assigning Roles:**

```php
// Single role
$user->assignRole('administrator'); // by role name
$user->assignRole($roleModel); // by role model

// Multiple roles in one call
$user->assignRole('administrator', 'editor');
$user->assignRole([$roleModel, $roleId, 'moderator']);

// Sync (replaces all)
$user->syncRoles(['administrator', 'editor']);
$user->syncRoles([$role1->id, $role2->id]);

// Sync without detaching existing
$user->syncRolesWithoutDetaching(['moderator']);

// Revoke
$user->revokeRole('editor');
$user->revokeRole($roleModel);
$user->revokeRoles(); // Revoke all
```

**Checking Roles:**

```php
// Single role
$user->hasRole('administrator');              // true/false

// Multiple roles
$user->hasAllRoles(['admin', 'editor']);     // Must have ALL
$user->hasAnyRole(['admin', 'moderator']);   // Must have ANY

// Get role names
$user->getRoleNames(); // ['administrator', 'editor']

// Get role labels keyed by name (falls back to name when no label)
$user->getRoleLabels(); // ['administrator' => 'Administrator', 'editor' => 'editor']
```

### Permission Management

**Assigning to Roles:**

```php
// Single permission
$role->givePermissionTo('users.create'); // by permission name
$role->givePermissionTo($permissionModel); // by permission model

// Multiple permissions in one call
$role->givePermissionTo('users.create', 'users.edit');
$role->givePermissionTo([$permissionModel, $permissionId, 'users.delete']);

// Sync (replaces all)
$role->syncPermissions(['users.create', 'users.edit']);
$role->syncPermissions([$perm1->id, $perm2->id]);

// Revoke
$role->revokePermissionTo('users.delete');
$role->revokePermissionTo($permissionModel);
$role->revokeAllPermissions();
```

**Checking Role Permissions:**

```php
$role->hasPermission('users.edit');    // Check if role has permission
$role->getPermissionNames();             // Get all permission names
```

**Checking User Permissions:**

```php
// Check by name
$user->hasPermission('users.create');

// Check by model
$user->hasPermission($permissionModel);

// Wildcard matching
$user->hasPermission('posts.*');

// Get all permissions inherited from roles
$user->getPermissions();

// Get permission names array
$user->getPermissionNames(); // ['users.create', 'users.edit']
```

### Checking Access

**Role Checking:**

```php
if ($user->hasRole('administrator')) {
    // User has administrator role
}

if ($user->hasAllRoles(['admin', 'editor'])) {
    // User has both roles
}

if ($user->hasAnyRole(['admin', 'moderator'])) {
    // User has at least one role
}

// Get all role names
$user->getRoleNames(); // ['administrator', 'editor']
```

**Permission Checking:**

```php
if ($user->hasPermission('users.create')) {
    // User can create users
}

if ($user->hasPermission('posts.*')) {
    // User has wildcard permission for posts
}
```

### Middleware

All middleware supports multiple values (requires ANY):

```php
// Role middleware
Route::middleware('role:administrator')->get('/admin', [AdminController::class, 'index']);

// Multiple roles (requires ANY)
Route::middleware('role:admin,editor')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Permission middleware
Route::middleware('permission:users.create')->post('/users', [UserController::class, 'store']);

// Multiple permissions (requires ANY)
Route::middleware('permission:users.create,users.edit')->put('/users/{id}', [UserController::class, 'update']);

// Role OR permission middleware
Route::middleware('role_or_permission:admin,users.create')->get('/users', [UserController::class, 'index']);

// Multiple role_or_permission
Route::middleware('role_or_permission:admin,editor,posts.manage')->group(function () {
    Route::post('/manage', [Controller::class, 'handle']);
});
```

**Responses:** unauthenticated requests receive **401**; authenticated users lacking access receive **403** (via `PermissionDeniedException`). Middleware aliases can be renamed in the `middleware` section of the config.

### Gate Integration

Custodian registers a single `Gate::before` hook that resolves any ability as a permission or role at check time. New roles and permissions are usable immediately — no cache to clear, no gates to re-register. When the ability is not granted by Custodian, the hook returns `null`, so your own `Gate::define` gates and policies still run as normal:

```php
// In controllers
public function store(Request $request)
{
    $this->authorize('users.create');
    // User can create users
}

// Using Gate facade
use Illuminate\Support\Facades\Gate;

if (Gate::allows('users.create')) {
    // Allowed
}

if (Gate::denies('users.delete')) {
    abort(403, 'Permission denied');
}

// Check for specific user
if (Gate::forUser($otherUser)->allows('posts.edit')) {
    // That user can edit posts
}

// Authorize roles
$this->authorize('administrator');
```

### Blade Directives

Custodian provides custom Blade directives for role checking, in addition to Laravel's built-in `@can` directives. All directives render nothing for guests — no need to wrap them in `@auth`:

**Custom Role Directives:**

```blade
@role('administrator')
    <div class="admin-panel">
        <h1>Admin Dashboard</h1>
    </div>
@endrole

@hasrole('editor')
    <p>Editor content here</p>
@endhasrole

@hasanyrole(['administrator', 'moderator'])
    <p>Content for admins or moderators</p>
@endhasanyrole

@hasallroles(['administrator', 'editor'])
    <p>Only for users with BOTH admin AND editor roles</p>
@endhasallroles
```

**Built-in Laravel Directives (via Gate integration):**

```blade
@can('users.create')
    <a href="/users/create">Create User</a>
@endcan

@canany(['users.create', 'users.edit'])
    <p>You can manage users</p>
@endcanany

@cannot('users.delete')
    <p>You cannot delete users</p>
@endcannot
```

### Artisan Commands

**Create a Role:**

```bash
php artisan custodian:create-role admin Administrator

# Optionally assign it to a user by ID, email, or name
php artisan custodian:create-role moderator "Moderator" 1
php artisan custodian:create-role moderator "Moderator" user@example.com
php artisan custodian:create-role moderator "Moderator" "Jane Doe"
```

**Create a Permission:**

```bash
php artisan custodian:create-permission users.create "Create Users"

# Optionally assign it to a role by ID or name
php artisan custodian:create-permission users.delete "Delete Users" 1
php artisan custodian:create-permission users.delete "Delete Users" admin
```

Both commands support Laravel Prompts when optional assignment arguments are omitted.

- `custodian:create-role` prompts for an optional user identifier and accepts a user ID, email, or name.
- `custodian:create-permission` prompts for an optional role identifier and accepts a role ID or role name.

**Upgrade helper:**

```bash
php artisan custodian:upgrade
```

Applies automated code rewrites when upgrading between Custodian versions. There's nothing to upgrade yet on this first release — see the [Upgrade Guide](UPGRADE.md).

**Diagnose configuration problems:**

```bash
php artisan custodian:doctor
```

Checks that the configured `role`/`permission`/`user` models exist and implement the right contracts, that the `roles`/`permissions` tables exist, and that the wildcard config is valid — useful right after installing or changing `config/custodian.php`.

### Query Scopes

```php
// Users with a specific role
User::query()->withRoles('administrator')->get();

// Users with a specific permission inherited through roles
User::query()->withPermissions('users.create')->get();

// Role scopes
Role::query()->protected()->get();
Role::query()->unprotected()->get();

// Permission scopes
Permission::query()->wildcard()->get();
Permission::query()->byGroup('users')->get();
```

## Models Reference

<details>
<summary><strong>User Model (via Traits)</strong></summary>

**Roleable trait provides:**

- `roles()` - BelongsToMany relationship
- `assignRole(...$roles)` - Assign one or more roles
- `syncRoles(array $roles, bool $detach = true)` - Sync roles
- `syncRolesWithoutDetaching(array $roles)` - Sync without detaching
- `revokeRole($role)` - Revoke specific role
- `revokeRoles()` - Revoke all roles
- `getRoleNames()` - Get all role names
- `getRoleLabels()` - Get role labels keyed by name
- `hasRole($role)` - Check single role
- `hasAllRoles(...$roles)` - Check all roles
- `hasAnyRole(...$roles)` - Check any role
- `getPermissionNames()` - Get permission names inherited from roles
- `hasPermission($permission)` - Check permission (by name or model)
- `getPermissions()` - Get all permissions inherited from roles

</details>

<details>
<summary><strong>Role Model</strong></summary>

**Properties:**

- `name` (string, unique)
- `label` (string, nullable)
- `description` (text, nullable)
- `is_protected` (boolean)

**Methods:**

- `getName()` - Get role name
- `isProtectedRole()` - Check if guarded
- `getPermissionNames()` - Get assigned permission names
- `permissions()` - BelongsToMany to permissions
- `users()` - BelongsToMany to users

**Scopes:**

- `protected()` - Only protected roles
- `unprotected()` - Only unprotected roles

</details>

<details>
<summary><strong>Permission Model</strong></summary>

**Properties:**

- `name` (string, unique)
- `label` (string, nullable)
- `description` (text, nullable)
- `group` (string, nullable, indexed)
- `is_wildcard` (boolean, auto-set)

**Methods:**

- `getName()` - Get permission name
- `getLabel()` - Get human-readable label
- `getDescription()` - Get description
- `isWildcard()` - Check if wildcard pattern
- `getGroup()` - Get resource group (e.g., 'users')
- `getType()` - Get PermissionType enum from the name's last segment (null if not a known action)
- `roles()` - BelongsToMany to roles

**Scopes:**

- `wildcard()` - Only wildcard permissions
- `byGroup($group)` - Filter by group

</details>

<details>
<summary><strong>Custodian Facade</strong></summary>

Utility helpers used internally to derive table names — available if you need the same conventions (e.g. in your own migrations):

```php
use AmdadulHaq\Custodian\Facades\Custodian;

Custodian::getSingularName('roles');          // 'role'
Custodian::getTableName(Role::class);         // 'roles' (resolves the model's table)
Custodian::getPivotTableName([Role::class, User::class]); // 'role_user' (alphabetical)
```

Both models also expose `getTable()`, which resolves the table name from `config('custodian.tables.*')`.

</details>

## Exceptions

```php
use AmdadulHaq\Custodian\Exceptions\PermissionDeniedException;
use AmdadulHaq\Custodian\Exceptions\ProtectedRoleException;

// Thrown by the middleware when a user lacks the required permission/role.
// Extends Symfony's HttpException, so it renders as an HTTP 403 response.
throw PermissionDeniedException::create('users.delete');
throw PermissionDeniedException::roleNotAssigned('administrator');
throw PermissionDeniedException::roleOrPermissionNotAssigned('admin, users.delete');

// Thrown when deleting a role with is_protected = true
throw ProtectedRoleException::cannotDelete('super-admin');
```

Role and permission mutators (`assignRole`, `givePermissionTo`, `syncRoles`, `revokeRole`, ...) throw `Illuminate\Database\Eloquent\ModelNotFoundException` when a name does not resolve to an existing model — typos fail loudly instead of silently doing nothing.

## Events

Every role/permission mutation dispatches an event, so you can hook your own audit logging, notifications, or cache invalidation without the package imposing a schema on you:

```php
use AmdadulHaq\Custodian\Events\RoleAssigned;
use AmdadulHaq\Custodian\Events\RoleRevoked;
use AmdadulHaq\Custodian\Events\PermissionGranted;
use AmdadulHaq\Custodian\Events\PermissionRevoked;

// Dispatched by $user->assignRole()/syncRoles() and $user->revokeRole()/revokeRoles()
class RoleAssigned { public Model $subject; public array $roleIds; }
class RoleRevoked { public Model $subject; public ?Model $role; } // $role is null when all roles were revoked

// Dispatched by $role->givePermissionTo()/syncPermissions() and $role->revokePermissionTo()/revokeAllPermissions()
class PermissionGranted { public Model $role; public array $permissionIds; }
class PermissionRevoked { public Model $role; public ?Model $permission; } // null when all permissions were revoked
```

```php
// app/Providers/EventServiceProvider.php (or a listener class)
Event::listen(function (RoleAssigned $event) {
    Log::info("Role(s) assigned to {$event->subject->getKey()}", ['role_ids' => $event->roleIds]);
});
```

## Performance

Permission checks are memoized per model instance, so repeated `hasPermission()` calls within a request hit the database once. Role checks use the loaded `roles` relation, which Custodian refreshes automatically after any role mutation.

## Database Structure

<details>
<summary><strong>Roles Table</strong></summary>

```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('label')->nullable();
    $table->text('description')->nullable();
    $table->boolean('is_protected')->default(false);
    $table->timestamps();
});
```

</details>

<details>
<summary><strong>Permissions Table</strong></summary>

```php
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('label')->nullable();
    $table->text('description')->nullable();
    $table->string('group')->nullable()->index();
    $table->boolean('is_wildcard')->default(false);
    $table->timestamps();
});
```

</details>

<details>
<summary><strong>Permission-Role Pivot</strong></summary>

```php
Schema::create('permission_role', function (Blueprint $table) {
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->primary(['permission_id', 'role_id']);
});
```

</details>

<details>
<summary><strong>Role-User Pivot</strong></summary>

```php
Schema::create('role_user', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->primary(['role_id', 'user_id']);
});
```

</details>

## Enums

### PermissionType

```php
use AmdadulHaq\Custodian\Enums\PermissionType;

PermissionType::CREATE->label();       // "Create"
PermissionType::READ->label();         // "Read"
PermissionType::WRITE->label();        // "Write"
PermissionType::UPDATE->label();       // "Update"
PermissionType::DELETE->label();       // "Delete"
PermissionType::VIEW_ANY->label();     // "View any"
PermissionType::VIEW->label();         // "View"
PermissionType::RESTORE->label();      // "Restore"
PermissionType::FORCE_DELETE->label(); // "Force delete"
PermissionType::MANAGE->label();       // "Manage"
```

## Development

### Code Quality Tools

```bash
# Rector (code refactoring)
composer refactor
composer refactor:check

# Laravel Pint (code style)
composer lint
composer lint:check

# Pest (testing)
composer test
composer test-coverage

# Larastan (static analysis)
composer analyse
```


## Troubleshooting

### Common Issues

<details>
<summary><strong>Class 'AmdadulHaq\Custodian\Concerns\Roleable' not found</strong></summary>

Solution:

```bash
composer dump-autoload
```

</details>

<details>
<summary><strong>Target class [role] does not exist.</strong></summary>

Solution:

```bash
php artisan config:clear
```

</details>

<details>
<summary><strong>Permissions not being recognized</strong></summary>

Permissions are resolved live from the database — make sure the permission exists, is assigned to one of the user's roles, and that you're checking a fresh model instance (`$user->fresh()`) if roles were changed on a different instance.

</details>

### Performance Tips

1. **Use wildcard permissions** to reduce permission count
2. **Filter at database level** instead of loading all users:

    ```php
    //  Good
    User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->get();

    //  Less efficient
    User::all()->filter(fn ($u) => $u->hasRole('admin'));
    ```

3. **Eager load** when needed:
    ```php
    User::with(['roles', 'roles.permissions'])->get();
    ```

## FAQ

<details>
<summary><strong>Can I use this with Laravel Sanctum?</strong></summary>

Yes! Custodian works seamlessly with Sanctum and any auth system.

</details>

<details>
<summary><strong>Can users have permissions without roles?</strong></summary>

No, users receive permissions via roles.

</details>

<details>
<summary><strong>How do wildcard permissions work?</strong></summary>

Create a permission like `posts.*` and it automatically matches `posts.create`, `posts.edit`, etc.

</details>

<details>
<summary><strong>Can I customize table names?</strong></summary>

Yes, publish the config and modify the `tables` section.

</details>

<details>
<summary><strong>Does it work with multiple guards?</strong></summary>

Yes, it integrates with Laravel's authorization system.

</details>

<details>
<summary><strong>Is there a UI for managing roles?</strong></summary>

Custodian is backend-only. For a UI, consider Filament Shield or build your own.

</details>

<details>
<summary><strong>What Blade directives does Custodian provide?</strong></summary>

Custodian ships with `@role`, `@hasrole`, `@hasanyrole`, and `@hasallroles`. Laravel's built-in `@can`, `@canany`, and `@cannot` also work through Gate integration.

</details>

<details>
<summary><strong>Can permissions be assigned to permissions?</strong></summary>

No, permissions are assigned to roles.

</details>

## Contributing

We welcome contributions! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## Security

Please review [our security policy](../../security/policy) for reporting vulnerabilities.

## Star History

If Custodian helps you, a star helps the project grow.

[![GitHub Stars](https://img.shields.io/github/stars/amdad121/custodian-laravel?style=social)](https://github.com/amdad121/custodian-laravel/stargazers)

[![Star History Chart](https://api.star-history.com/svg?repos=amdad121/custodian-laravel&type=Date)](https://star-history.com/#amdad121/custodian-laravel&Date)

## Credits

![Contributors](https://contrib.rocks/image?repo=amdad121/custodian-laravel)

## License

The MIT License (MIT). See [License File](LICENSE.md) for details.

---

<p align="center">Made with ❤️ for the Laravel community</p>
