# Changelog

All notable changes to `custodian-laravel` will be documented in this file.

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
