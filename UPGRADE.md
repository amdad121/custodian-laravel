# Upgrade Guide

Current Version: **v2.1.2**

## Upgrading to the next release (from v2.1.x)

Laravel 11 is no longer supported.

- **Action:** upgrade your application to Laravel 12 or 13 before updating Custodian.

The `Gate::before` hook no longer grants checks that pass arguments. `$user->can('posts.edit', $post)` now goes straight to your `PostPolicy` (or a `Gate::define`), even if the user has the `posts.edit` permission. Users with the literal `*` permission are the exception: they still pass every check.

- **Action:** search for `can(`, `cannot(`, `authorize(`, `Gate::allows(` and `@can` calls that pass a model and rely on a Custodian permission or role. Either drop the argument, or check the permission inside the policy: `return $user->hasPermission('posts.edit');`.

Sync and revoke events now describe what actually changed.

- **Action:** if a listener reads `RoleAssigned::$roleIds` or `PermissionGranted::$permissionIds`, expect only the newly attached IDs (from `assignRole()`, `givePermissionTo()` and the sync methods). The events are not dispatched when nothing changed.
- **Action:** listeners can use the new `RoleRevoked::$roleIds` and `PermissionRevoked::$permissionIds` to see which IDs were detached.

## Upgrading from v1.0.0

`is_guarded` was renamed to `is_protected` to avoid colliding with Eloquent's own `$guarded` mass-assignment property, which sat right next to it on the `Role` model.

- **Action:** rename the `is_guarded` column to `is_protected` in your `roles` table migration (or add a new migration renaming it in place).
- **Action:** replace `GuardedRoleException` with `ProtectedRoleException` in any `catch` blocks or `expectException`/`toThrow` assertions.
- **Action:** replace calls to the `guarded()` and `unguarded()` query scopes with `protected()` and `unprotected()`.
- Run `php artisan custodian:upgrade` to automatically rewrite these identifiers across your `app/`, `database/` (except `database/migrations/`, which you edit by hand), `resources/views/`, and `tests/` directories.
