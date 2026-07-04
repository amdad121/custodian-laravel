<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Events;

use Illuminate\Database\Eloquent\Model;

/** Dispatched after one or more permissions are granted to a role. */
class PermissionGranted
{
    /**
     * @param  array<int, int>  $permissionIds
     */
    public function __construct(
        public readonly Model $role,
        public readonly array $permissionIds,
    ) {}
}
