# Changelog

All notable changes to `custodian-laravel` will be documented in this file.

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
