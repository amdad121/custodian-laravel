<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Events;

use Illuminate\Database\Eloquent\Model;

/** Dispatched after one or more roles are assigned to a model. */
class RoleAssigned
{
    /**
     * @param  array<int, int>  $roleIds  Role IDs that were assigned (or attempted, via sync)
     */
    public function __construct(
        public readonly Model $subject,
        public readonly array $roleIds,
    ) {}
}
