<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Events;

use Illuminate\Database\Eloquent\Model;

/** Dispatched after one or more roles are revoked from a model. */
class RoleRevoked
{
    public function __construct(
        public readonly Model $subject,
        public readonly ?Model $role = null,
    ) {}
}
