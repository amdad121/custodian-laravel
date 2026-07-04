<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Contract for models that use Custodian with role and permission checks.
 */
interface Roleable
{
    /**
     * Get the roles associated with the entity.
     *
     * @return BelongsToMany<Model, covariant Model>
     */
    public function roles(): BelongsToMany;

    /**
     * Get the names of all roles.
     *
     * @return array<int, string>
     */
    public function getRoleNames(): array;

    /**
     * Get the labels of all roles, keyed by role name.
     *
     * @return array<string, string>
     */
    public function getRoleLabels(): array;

    /**
     * Assign a role to the entity.
     *
     * @param  Model|string|int|array<int, Model|string|int>  ...$roles
     */
    public function assignRole(Model|string|int|array ...$roles): self;

    /**
     * Sync roles to the entity.
     *
     * @param  array<int, Model|string|int>  $roles
     * @return array<string, mixed>
     */
    public function syncRoles(array $roles): array;

    /**
     * Revoke a role from the entity.
     */
    public function revokeRole(Model|string $role): int;

    /**
     * Check if the entity has a specific role.
     *
     * @param  string|array<array-key, mixed>|Collection<array-key, mixed>  $role
     */
    public function hasRole(string|array|Collection $role): bool;

    /**
     * Check if the entity has all specified roles.
     *
     * @param  string|array<array-key, mixed>|Collection<array-key, mixed>  ...$roles
     */
    public function hasAllRoles(string|array|Collection ...$roles): bool;

    /**
     * Check if the entity has any of the specified roles.
     *
     * @param  string|array<array-key, mixed>|Collection<array-key, mixed>  ...$roles
     */
    public function hasAnyRole(string|array|Collection ...$roles): bool;

    /**
     * Get all permissions inherited through roles.
     *
     * @return Collection<int, Model>
     */
    public function getPermissions(): Collection;

    /**
     * Get all inherited permission names.
     *
     * @return array<int, string>
     */
    public function getPermissionNames(): array;

    /**
     * Check if the user has a specific permission through roles.
     */
    public function hasPermission(Model|string $permission): bool;
}
