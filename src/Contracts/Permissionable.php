<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Contract for entities that have and can manage permissions.
 */
interface Permissionable
{
    /**
     * Get the permissions associated with the entity.
     *
     * @return BelongsToMany<Model, covariant Model>
     */
    public function permissions(): BelongsToMany;

    /**
     * Get the names of all permissions.
     *
     * @return array<int, string>
     */
    public function getPermissionNames(): array;

    /**
     * Give permission to the entity.
     *
     * @param  Model|string|int|array<int, Model|string|int>  ...$permissions
     */
    public function givePermissionTo(Model|string|int|array ...$permissions): Model;

    /**
     * Sync permissions to the entity.
     *
     * @param  array<int, Model|string|int>  $permissions
     * @return array<string, mixed>
     */
    public function syncPermissions(array $permissions): array;

    /**
     * Revoke permission from the entity.
     */
    public function revokePermissionTo(Model|string $permission): int;

    /**
     * Revoke all permissions from the entity.
     */
    public function revokeAllPermissions(): int;

    /**
     * Check if the entity has a specific permission by model or name.
     */
    public function hasPermission(Model|string $permission): bool;
}
