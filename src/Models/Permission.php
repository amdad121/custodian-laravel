<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Models;

use AmdadulHaq\Custodian\Concerns\HasCustodianHelpers;
use AmdadulHaq\Custodian\Enums\PermissionType;
use AmdadulHaq\Custodian\Facades\Custodian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

/**
 * @property string $name
 * @property string|null $label
 * @property string|null $description
 * @property string|null $group
 * @property bool $is_wildcard
 */
class Permission extends Model
{
    use HasCustodianHelpers;

    protected $guarded = [];

    /**
     * Get the permission name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_wildcard' => 'boolean',
        ];
    }

    /**
     * Get the permission label.
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Get the permission description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get the permission table.
     */
    public function getTable(): string
    {
        return config('custodian.tables.permissions', parent::getTable());
    }

    /**
     * Get the permission roles.
     *
     * @return BelongsToMany<Model, $this>
     */
    public function roles(): BelongsToMany
    {
        /** @var class-string<Model> $roleModel */
        $roleModel = config('custodian.models.role');

        return $this->belongsToMany(
            $roleModel,
            Custodian::getPivotTableName(Arr::only(config('custodian.models'), ['permission', 'role'])),
            Custodian::getSingularName($this->getTable()).'_id',
            Custodian::getSingularName(Custodian::getTableName($roleModel)).'_id'
        );
    }

    /**
     * Check if the permission is a wildcard permission.
     */
    public function isWildcard(): bool
    {
        return str_ends_with($this->name, '*');
    }

    /**
     * Get the permission group.
     */
    public function getGroup(): string
    {
        return explode('.', $this->name)[0];
    }

    /**
     * Get the permission action type (e.g., 'read', 'write', 'delete').
     */
    public function getType(): ?PermissionType
    {
        $parts = explode('.', $this->name);
        $action = end($parts);

        return PermissionType::tryFrom($action);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::saving(function (self $permission): void {
            if ($permission->isDirty('name')) {
                $permission->is_wildcard = str_ends_with($permission->name, '*');
            }
        });
    }

    /**
     * Scope for wildcard permissions.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeWildcard(Builder $query): Builder
    {
        return $query->where('name', 'like', '%*');
    }

    /**
     * Scope for permissions by group.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('name', 'like', $group.'.%');
    }
}
