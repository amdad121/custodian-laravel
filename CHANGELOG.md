# Changelog

All notable changes to `custodian-laravel` will be documented in this file.

## Unreleased

- Deleting a role no longer loads every user that held it into memory: only IDs are captured before the delete, and users are loaded in chunks to dispatch `RoleRevoked`. Deleting a role also dispatches one `PermissionRevoked` with the role's permission IDs.
- All four events now implement `ShouldDispatchAfterCommit`, so a mutation inside a rolled-back transaction no longer dispatches events.
- Middleware returns 403 instead of 401 when the logged-in user's model doesn't use `Roleable`. Guests still get 401.
- Assigning a role or permission no longer throws a unique-constraint error when a concurrent request assigned it first.
- Docs: what `is_protected` covers, and that an empty `group` is refilled from the name.
- Deleting a role with `$role->delete()` now dispatches `RoleRevoked` for every user that held it, and deleting a permission dispatches `PermissionRevoked` for every role that held it. Before, the pivot rows cascaded silently and audit listeners never heard about them.
- `Permission::is_wildcard` is now recalculated on every save, so updating it by hand can no longer leave it out of sync with the name.
- `Permission::group` is now filled from the first segment of the name when it is not given.
- Middleware now accepts pipe-separated items (`role:admin|editor`) as well as commas.
- Docs: `Role::hasPermission()` checks exact permissions only (wildcards are resolved on the user), and `Role`/`Permission` allow mass assignment of every column.
- **Breaking:** dropped Laravel 11 support. Custodian now requires Laravel 12 or 13.
- The `Role` and `Permission` query scopes now use Laravel 12's `#[Scope]` attribute (`protected()`, `unprotected()`, `wildcard()`, `byGroup()`). Calling them as `Role::query()->protected()` is unchanged; only direct calls to the old `scopeProtected()`-style methods need updating.
- **Breaking:** the `Gate::before` hook no longer grants checks that carry arguments (`$user->can('update', $post)`, `$this->authorize('posts.edit', $post)`). Those go to your policies/gates. Before, a role or permission named after a policy method (e.g. `update`) passed that policy for every model. Checks without arguments are unchanged. A user with the literal `*` permission still passes every check, with or without arguments.
- **Breaking:** `RoleAssigned`/`PermissionGranted` from `assignRole()`/`givePermissionTo()`/`syncRoles()`/`syncPermissions()` now carry only the IDs actually attached, and are not dispatched when nothing was attached. `RoleRevoked`/`PermissionRevoked` gain a `roleIds`/`permissionIds` property listing the detached IDs, and are not dispatched by `revokeRole()`/`revokePermissionTo()` when nothing was detached.
- Fixed: `hasAllRoles(collect([...]))` required only one of the roles; it now requires all. `hasAllRoles()` with no roles now returns `false`.
- Fixed: wildcard permissions with a `0` segment (`api.0.*`) matched too broadly, and `posts.*` matched the bare ability `posts`.
- Fixed: `custodian:upgrade` no longer rewrites files in `database/migrations/`, which broke fresh migrations for the migration that created (or renamed) `is_guarded`.
- Fixed: roles/permissions with numeric names (e.g. `"2024"`) can now be resolved by name when no record has that ID. Strings like `"1e3"` are no longer treated as IDs.
- Fixed: `custodian:create-role` refuses a user name or email that matches more than one user, instead of assigning the role to whichever came first.
- Fixed: `Permission::byGroup()` treated `_` and `%` in the group name as SQL wildcards.
- Fixed: Blade role directives no longer crash when the authenticated user is not `Roleable`.
- Fixed: empty items in middleware parameters (`permission:a,`) are ignored.

## v2.1.2 - 2026-07-28

- Fixed: `hasRole()`, `hasAllRoles()`, and `hasAnyRole()` now accept a `Role` model instance (previously only `string|array|Collection`, so passing a model threw a `TypeError` — `hasPermission()` already allowed this).

## v2.1.0 - 2026-07-13

- `custodian:upgrade` now also scans `resources/views/` and `tests/` directories, in addition to `app/` and `database/`.
- `custodian:doctor` now also checks that the derived `role<->permission` and `role<->user` pivot tables exist, that `custodian.middleware.*` aliases are configured, and that the roles table has the `is_protected` column (catches an incomplete v1→v2 upgrade).
- Fixed: `syncRoles()` and `Role::syncPermissions()` now dispatch `RoleRevoked`/`PermissionRevoked` when the sync actually detaches existing roles/permissions (previously only `RoleAssigned`/`PermissionGranted` fired, so audit listeners never saw the detach).
- Fixed: `Permission::is_wildcard` is now recalculated whenever `name` changes on update, not just on creation.
- `Roleable` and `Permissionable` contracts now declare `syncRolesWithoutDetaching()`, `revokeRoles()`, and `revokeAllPermissions()` to match the trait/model's actual public API.

## v2.0.0 - 2026-07-11

- **Breaking:** renamed `is_guarded` to `is_protected` (and `GuardedRoleException` to `ProtectedRoleException`, `guarded()`/`unguarded()` scopes to `protected()`/`unprotected()`) to avoid colliding with Eloquent's own `$guarded` mass-assignment property. See [UPGRADE.md](UPGRADE.md).

## v1.0.0 - 2026-07-04

Initial release.

- Role and permission management for Laravel, with permissions granted to users only through roles.
- Real-time authorization via a single `Gate::before` hook — no caching layer, no boot-time gate registration.
- `role`, `permission`, and `role_or_permission` middleware.
- Blade directives: `@role`, `@hasrole`, `@hasanyrole`, `@hasallroles`.
- Wildcard permissions (`posts.*`).
- Guarded roles that cannot be deleted (`GuardedRoleException`).
- Artisan commands: `custodian:create-role`, `custodian:create-permission`, `custodian:upgrade`, `custodian:doctor`.
- Events dispatched on every role/permission mutation: `RoleAssigned`, `RoleRevoked`, `PermissionGranted`, `PermissionRevoked`.
- Configurable models, tables, and middleware aliases.
- Supports Laravel 11, 12, and 13 on PHP 8.2+.
