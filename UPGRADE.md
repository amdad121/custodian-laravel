# Upgrade Guide

Current Version: **v2.1.0**

## Upgrading from v1.0.0

`is_guarded` was renamed to `is_protected` to avoid colliding with Eloquent's own `$guarded` mass-assignment property, which sat right next to it on the `Role` model.

- **Action:** rename the `is_guarded` column to `is_protected` in your `roles` table migration (or add a new migration renaming it in place).
- **Action:** replace `GuardedRoleException` with `ProtectedRoleException` in any `catch` blocks or `expectException`/`toThrow` assertions.
- **Action:** replace calls to the `guarded()` and `unguarded()` query scopes with `protected()` and `unprotected()`.
- Run `php artisan custodian:upgrade` to automatically rewrite these identifiers across your `app/`, `database/`, `resources/views/`, and `tests/` directories.
