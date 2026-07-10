<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Models;

use AmdadulHaq\Custodian\Concerns\HasCustodianHelpers;
use AmdadulHaq\Custodian\Contracts\Permissionable as PermissionableContract;
use AmdadulHaq\Custodian\Events\PermissionGranted;
use AmdadulHaq\Custodian\Events\PermissionRevoked;
use AmdadulHaq\Custodian\Exceptions\ProtectedRoleException;
use AmdadulHaq\Custodian\Facades\Custodian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

/**
 * @property string $name
 * @property string|null $label
 * @property string|null $description
 * @property bool $is_protected
 */
class Role extends Model implements PermissionableContract
{
    use HasCustodianHelpers;

    protected $guarded = [];

    /**
     * Prevent protected roles from being deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $role): void {
            if ($role->isProtectedRole()) {
                throw ProtectedRoleException::cannotDelete($role->getName());
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_protected' => 'boolean',
        ];
    }

    /**
     * Get the role name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the role label.
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Get the role description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get table name.
     */
    public function getTable(): string
    {
        return config('custodian.tables.roles', parent::getTable());
    }

    /**
     * Permissions relation.
     *
     * @return BelongsToMany<Model, $this>
     */
    public function permissions(): BelongsToMany
    {
        /** @var class-string<Model> $permissionModel */
        $permissionModel = config('custodian.models.permission');

        return $this->belongsToMany(
            $permissionModel,
            Custodian::getPivotTableName(Arr::only(config('custodian.models'), ['permission', 'role'])),
            Custodian::getSingularName($this->getTable()).'_id',
            Custodian::getSingularName(Custodian::getTableName($permissionModel)).'_id'
        );
    }

    /**
     * Users relation.
     *
     * @return BelongsToMany<Model, $this>
     */
    public function users(): BelongsToMany
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('custodian.models.user');

        return $this->belongsToMany(
            $userModel,
            Custodian::getPivotTableName(Arr::only(config('custodian.models'), ['role', 'user'])),
            Custodian::getSingularName($this->getTable()).'_id',
            Custodian::getSingularName(Custodian::getTableName($userModel)).'_id'
        );
    }

    /**
     * Check if role is protected.
     */
    public function isProtectedRole(): bool
    {
        return $this->is_protected ?? false;
    }

    /**
     * Get permission names.
     *
     * @return array<int, string>
     */
    public function getPermissionNames(): array
    {
        return $this->permissions->pluck('name')->toArray();
    }

    /**
     * Give a permission to the role.
     *
     * @param  Model|string|int|array<int, Model|string|int>  ...$permissions
     */
    public function givePermissionTo(Model|string|int|array ...$permissions): Model
    {
        $permissionIds = $this->getModelIds('permission', $this->flattenArgs($permissions));

        $this->permissions()->syncWithoutDetaching($permissionIds);
        $this->unsetRelation('permissions');

        event(new PermissionGranted($this, $permissionIds));

        return $this;
    }

    /**
     * Sync permissions to the role.
     *
     * @param  array<int, Model|string|int>  $permissions
     * @return array<string, mixed>
     */
    public function syncPermissions(array $permissions): array
    {
        $permissionIds = $this->getModelIds('permission', $permissions);
        $synced = $this->permissions()->sync($permissionIds);
        $this->unsetRelation('permissions');

        event(new PermissionGranted($this, $permissionIds));

        return $synced;
    }

    /**
     * Revoke a permission from the role.
     */
    public function revokePermissionTo(Model|string $permission): int
    {
        $permission = $this->resolveModel('permission', $permission);

        $detached = $this->permissions()->detach($permission);
        $this->unsetRelation('permissions');

        event(new PermissionRevoked($this, $permission));

        return $detached;
    }

    /**
     * Revoke all permissions from the role.
     */
    public function revokeAllPermissions(): int
    {
        $detached = $this->permissions()->detach();
        $this->unsetRelation('permissions');

        event(new PermissionRevoked($this));

        return $detached;
    }

    /**
     * Check if the role has a permission assigned directly.
     */
    public function hasPermission(Model|string $permission): bool
    {
        $permission = $this->resolveModel('permission', $permission, false);

        return $permission instanceof Model && $this->permissions()
            ->whereKey($permission->getKey())
            ->exists();
    }

    /**
     * Scope a query to only include protected roles.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeProtected(Builder $query): Builder
    {
        return $query->where('is_protected', true);
    }

    /**
     * Scope a query to only include unprotected roles.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeUnprotected(Builder $query): Builder
    {
        return $query->where('is_protected', false);
    }
}
