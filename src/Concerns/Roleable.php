<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Concerns;

use AmdadulHaq\Custodian\Events\RoleAssigned;
use AmdadulHaq\Custodian\Events\RoleRevoked;
use AmdadulHaq\Custodian\Facades\Custodian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait Roleable
{
    use HasCustodianHelpers;

    /**
     * Per-instance memo of permissions inherited through roles.
     *
     * @var Collection<int, Model>|null
     */
    protected ?Collection $memoizedCustodianPermissions = null;

    /**
     * Forget loaded role/permission state after a mutation.
     */
    protected function flushCustodianState(): void
    {
        $this->unsetRelation('roles');
        $this->memoizedCustodianPermissions = null;
    }

    /**
     * Get the roles relation.
     *
     * @return BelongsToMany<Model, $this>
     */
    public function roles(): BelongsToMany
    {
        /** @var class-string<Model> $roleModel */
        $roleModel = config('custodian.models.role');

        return $this->belongsToMany(
            $roleModel,
            Custodian::getPivotTableName(Arr::only(config('custodian.models'), ['role', 'user'])),
            Custodian::getSingularName($this->getTable()).'_id',
            Custodian::getSingularName(Custodian::getTableName($roleModel)).'_id'
        );
    }

    /**
     * Assign a role to the model.
     *
     * @param  Model|string|int|array<int, Model|string|int>  ...$roles
     */
    public function assignRole(Model|string|int|array ...$roles): self
    {
        $roleIds = $this->getModelIds('role', $this->flattenArgs($roles));

        $this->roles()->syncWithoutDetaching($roleIds);
        $this->flushCustodianState();

        event(new RoleAssigned($this, $roleIds));

        return $this;
    }

    /**
     * Sync roles to the model.
     *
     * @param  array<int, Model|string|int>  $roles
     * @return array<string, mixed>
     */
    public function syncRoles(array $roles, bool $detach = true): array
    {
        $roleIds = $this->getModelIds('role', $roles);

        $synced = $detach
            ? $this->roles()->sync($roleIds)
            : $this->roles()->syncWithoutDetaching($roleIds);

        $this->flushCustodianState();

        event(new RoleAssigned($this, $roleIds));

        return $synced;
    }

    /**
     * Sync roles to the model without detaching.
     *
     * @param  array<int, Model|string|int>  $roles
     * @return array<string, mixed>
     */
    public function syncRolesWithoutDetaching(array $roles): array
    {
        return $this->syncRoles($roles, false);
    }

    /**
     * Revoke a role from the model.
     */
    public function revokeRole(Model|string $role): int
    {
        $role = $this->resolveModel('role', $role);

        $detached = $this->roles()->detach($role);
        $this->flushCustodianState();

        event(new RoleRevoked($this, $role));

        return $detached;
    }

    /**
     * Revoke all roles from the model.
     */
    public function revokeRoles(): int
    {
        $detached = $this->roles()->detach();
        $this->flushCustodianState();

        event(new RoleRevoked($this));

        return $detached;
    }

    /**
     * Get the role names.
     *
     * @return array<int, string>
     */
    public function getRoleNames(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    /**
     * Get the role labels keyed by role name, falling back
     * to the name when a role has no label.
     *
     * @return array<string, string>
     */
    public function getRoleLabels(): array
    {
        return $this->roles
            ->mapWithKeys(fn (Model $role): array => [
                $role->getAttribute('name') => $role->getAttribute('label') ?? $role->getAttribute('name'),
            ])
            ->toArray();
    }

    /**
     * Check if entity has the given role.
     *
     * @param  string|array<array-key, mixed>|Collection<array-key, mixed>  $role
     */
    public function hasRole(string|array|Collection $role): bool
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return $this->normalizeRoleNames($role)
            ->intersect($this->getAssignedRoleNames())
            ->isNotEmpty();
    }

    /**
     * Check if entity has all of the given roles.
     *
     * @param  string|array<array-key, mixed>|Collection<array-key, mixed>  ...$roles
     */
    public function hasAllRoles(string|array|Collection ...$roles): bool
    {
        if (count($roles) === 1 && is_array($roles[0]) && $roles[0] !== []) {
            $roles = $roles[0];
        }

        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if entity has any of the given roles.
     *
     * @param  string|array<array-key, mixed>|Collection<array-key, mixed>  ...$roles
     */
    public function hasAnyRole(string|array|Collection ...$roles): bool
    {
        return $this->normalizeRoleNames($roles)
            ->intersect($this->getAssignedRoleNames())
            ->isNotEmpty();
    }

    /**
     * Get the names of all currently assigned roles.
     *
     * @return Collection<int, string>
     */
    protected function getAssignedRoleNames(): Collection
    {
        return $this->roles->pluck('name');
    }

    /**
     * Normalize a mixed role input into a flat Collection of name strings.
     *
     * @param  string|array<array-key, mixed>|Collection<array-key, mixed>  $roles
     * @return Collection<int, string>
     */
    protected function normalizeRoleNames(string|array|Collection $roles): Collection
    {
        if (is_string($roles)) {
            return collect([$roles]);
        }

        if ($roles instanceof Collection) {
            $roles = $roles->pluck('name')->all();
        }

        return collect($roles)->flatten()->filter();
    }

    /**
     * Scope a query to include models with specific roles.
     *
     * @param  Builder<Model>  $query
     * @param  string|array<int, string>  $roles
     * @return Builder<Model>
     */
    protected function scopeWithRoles(Builder $query, string|array $roles): Builder
    {
        return $query->whereHas('roles', fn (Builder $q) => $q->whereIn('name', (array) $roles));
    }

    /**
     * Get all permissions inherited through roles.
     *
     * @return Collection<int, Model>
     */
    public function getPermissions(): Collection
    {
        return $this->getPermissionsViaRoles()
            ->unique(fn (Model $permission) => $permission->getKey())
            ->values();
    }

    /**
     * Check if model has a permission by model or name.
     */
    public function hasPermission(Model|string $permission): bool
    {
        $name = is_string($permission)
            ? $permission
            : $permission->getAttribute('name');

        if (! is_string($name)) {
            return false;
        }

        $permissions = $this->getAllPermissionNames();

        if ($permissions->contains($name)) {
            return true;
        }

        if (! config('custodian.wildcard.enabled', true)) {
            return false;
        }

        return (bool) $this->matchesWildcardPermission($name, $permissions);
    }

    /**
     * Get the permission names inherited through roles.
     *
     * @return array<int, string>
     */
    public function getPermissionNames(): array
    {
        return $this->getAllPermissionNames()->all();
    }

    /**
     * Scope a query to include models with specific permissions.
     *
     * @param  Builder<Model>  $query
     * @param  string|array<int, string>  $permissions
     * @return Builder<Model>
     */
    protected function scopeWithPermissions(Builder $query, string|array $permissions): Builder
    {
        return $query->whereHas('roles.permissions', fn (Builder $q) => $q->whereIn('name', (array) $permissions));
    }

    /**
     * Get all permission names for the model.
     *
     * @return Collection<int, string>
     */
    protected function getAllPermissionNames(): Collection
    {
        return $this->getPermissions()->pluck('name');
    }

    /**
     * Check if a permission matches a wildcard permission.
     *
     * @param  Collection<int, string>  $allPermissions
     */
    protected function matchesWildcardPermission(string $permission, Collection $allPermissions): bool
    {
        $permissionParts = explode('.', $permission);

        return $allPermissions->contains(function (string $perm) use ($permissionParts): bool {
            if (! str_ends_with($perm, '*')) {
                return false;
            }

            $wildcardParts = array_filter(explode('.', rtrim($perm, '*')));

            foreach ($wildcardParts as $index => $part) {
                if (! isset($permissionParts[$index]) || $permissionParts[$index] !== $part) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Get permissions inherited through roles.
     *
     * @return Collection<int, Model>
     */
    protected function getPermissionsViaRoles(): Collection
    {
        return $this->memoizedCustodianPermissions ??= $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(function (Model $role): Collection {
                $permissions = $role->getRelationValue('permissions');

                return $permissions instanceof Collection ? $permissions : collect();
            });
    }
}
