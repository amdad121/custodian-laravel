<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Exceptions;

use RuntimeException;

class GuardedRoleException extends RuntimeException
{
    public static function cannotDelete(string $role): self
    {
        return new self(sprintf('Cannot delete guarded role: %s', $role));
    }
}
