<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Events;

use Illuminate\Database\Eloquent\Model;

/** Dispatched after one or more permissions are revoked from a role. */
class PermissionRevoked
{
    public function __construct(
        public readonly Model $role,
        public readonly ?Model $permission = null,
    ) {}
}
