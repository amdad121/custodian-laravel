<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Middleware;

use AmdadulHaq\Custodian\Concerns\ParsesMiddlewareParameters;
use AmdadulHaq\Custodian\Contracts\Roleable;
use AmdadulHaq\Custodian\Exceptions\PermissionDeniedException;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    use ParsesMiddlewareParameters;

    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        abort_unless($user instanceof Roleable, 401, 'Unauthenticated.');

        $flattenedRoles = $this->parseParameters($roles);

        if (! $user->hasAnyRole(...$flattenedRoles)) {
            throw PermissionDeniedException::roleNotAssigned(implode(', ', $flattenedRoles));
        }

        return $next($request);
    }
}
