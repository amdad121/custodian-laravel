<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;

/** Dispatched after one or more permissions are revoked from a role. */
class PermissionRevoked implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly Model $role,
        public readonly ?Model $permission = null,
        /** @var array<int, int> IDs actually detached; for a revoke-all, every ID that was attached. */
        public readonly array $permissionIds = [],
    ) {}
}
