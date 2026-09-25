<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Middleware;

use AmdadulHaq\Custodian\Concerns\ParsesMiddlewareParameters;
use AmdadulHaq\Custodian\Contracts\Roleable;
use AmdadulHaq\Custodian\Exceptions\PermissionDeniedException;
use Closure;
use Illuminate\Http\Request;

class RoleOrPermissionMiddleware
{
    use ParsesMiddlewareParameters;

    public function handle(Request $request, Closure $next, string ...$roleOrPermissions): mixed
    {
        $user = $request->user();

        abort_if($user === null, 401, 'Unauthenticated.');

        // Logged in, but as a model that cannot hold roles (e.g. another guard).
        if (! $user instanceof Roleable) {
            throw PermissionDeniedException::roleOrPermissionNotAssigned(implode(', ', $roleOrPermissions));
        }

        $flattenedItems = $this->parseParameters($roleOrPermissions);

        foreach ($flattenedItems as $item) {
            if ($user->hasRole($item) || $user->hasPermission($item)) {
                return $next($request);
            }
        }

        throw PermissionDeniedException::roleOrPermissionNotAssigned(implode(', ', $flattenedItems));
    }
}
