# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`amdadulhaq/custodian-laravel` — a role & permission package for Laravel 11/12/13 (PHP 8.2+). Users get permissions only through roles (no direct user→permission assignment). Tests run against Orchestra Testbench with an in-memory SQLite database.

## Commands

```bash
composer test                # Pest test suite
vendor/bin/pest tests/Unit/RoleTest.php          # single file
vendor/bin/pest --filter="assign role"           # single test by name
composer analyse             # PHPStan level 8 (larastan), src/ only
composer lint                # Pint (fix); composer lint:check to verify only
composer refactor            # Rector (apply); composer refactor:check for dry-run
```

All four gates (pest, phpstan, pint, rector dry-run) must pass before release; CI runs the matrix Laravel 11/12/13 × PHP 8.2–8.5.

## Architecture

**Authorization flow** — `CustodianServiceProvider::registerGateHook()` registers a single `Gate::before` hook that resolves any ability live: it grants when `$user->hasPermission($ability)` or `$user->hasRole($ability)`, and returns `null` otherwise so app-defined gates/policies still run. There is deliberately no caching layer; instead `Concerns\Roleable` memoizes permissions per model instance (`$memoizedCustodianPermissions`) and the provider memoizes the permissions-table existence check (positive results only — Octane workers live across requests and the table may appear mid-migration).

**Mutation invariant** — every role/permission mutator must call `flushCustodianState()` (user trait) or `unsetRelation('permissions')` (Role model) so `hasRole()`/`hasPermission()` are fresh on the same instance immediately after a change. Name-based lookups are strict: an unknown name throws `ModelNotFoundException` (never silently no-op — a null passed to `detach()` would detach everything).

**Configurable models** — `Role`, `Permission`, and the user model are resolved from `config('custodian.models.*')` everywhere (relations, `HasCustodianHelpers::resolveModel`, commands). Pivot table names are *derived* at runtime by `Custodian::getPivotTableName()` from the singularized table names of the two models, not configured directly. Table names come from `config('custodian.tables.*')` via `getTable()` overrides.

**Naming: contract vs trait** — `Contracts\Roleable` (interface) and `Concerns\Roleable` (trait) intentionally share a name; consumers alias the contract (`Roleable as RoleableContract`). The contract is load-bearing: middleware, the gate hook, and `custodian:create-role` all use `instanceof Roleable` as the type gate. Do not rename either.

**HTTP semantics** — middleware return 401 for unauthenticated requests, 403 (via `PermissionDeniedException extends HttpException`) for authenticated users lacking access. `ProtectedRoleException` blocks deletion of roles with `is_protected = true` (enforced in `Role::booted()`).

**Wildcards** — a permission named `posts.*` matches any `posts.…` ability (`matchesWildcardPermission`), toggled by `config('custodian.wildcard.enabled')`. `is_wildcard` is auto-set in `Permission::booted()` when the name ends with `*`.

**`custodian:upgrade`** — scans `app/` and `database/` for identifiers listed in the `REWRITES` regex map (`src/Commands/UpgradeCommand.php`) and rewrites them in place. When a future release removes or renames public API, add the old→new pattern to that map and add a corresponding test in `tests/Unit/UpgradeCommandTest.php`.

**`custodian:doctor`** — read-only diagnostic command (`src/Commands/DoctorCommand.php`) checking configured model classes, table existence, and wildcard config. When adding new config keys, add a corresponding check here.

**Events** — `assignRole`/`syncRoles`/`revokeRole`/`revokeRoles` (user trait) and `givePermissionTo`/`syncPermissions`/`revokePermissionTo`/`revokeAllPermissions` (Role model) each dispatch a `src/Events/*` event after the mutation and state flush. These are the only extension point for audit logging — the package itself has no persistence layer for history.

## Conventions

- PHPStan is at level 8 with full generics (`BelongsToMany<Model, $this>`, `Builder<self>`, `array<int, string>` etc.). `tests/Models` is included in the analysis paths alongside `src/` specifically so `Concerns\Roleable` gets analysed through the test `User` model (nothing in `src/` itself uses the trait).
- Migrations ship as `.php.stub` files in `database/migrations/` and are published with timestamps by the provider.
- On breaking changes, update all three docs together: README.md, UPGRADE.md (new numbered section with per-item **Action:** lines), CHANGELOG.md.
