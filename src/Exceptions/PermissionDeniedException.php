<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PermissionDeniedException extends HttpException
{
    public static function create(string $permission): self
    {
        return new self(403, sprintf('User does not have permission: %s', $permission));
    }

    public static function roleNotAssigned(string $role): self
    {
        return new self(403, sprintf('User does not have role: %s', $role));
    }

    public static function roleOrPermissionNotAssigned(string $items): self
    {
        return new self(403, sprintf('User does not have role or permission: %s', $items));
    }
}
