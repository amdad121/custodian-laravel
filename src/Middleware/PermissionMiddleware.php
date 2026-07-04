<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Middleware;

use AmdadulHaq\Custodian\Concerns\ParsesMiddlewareParameters;
use AmdadulHaq\Custodian\Contracts\Roleable;
use AmdadulHaq\Custodian\Exceptions\PermissionDeniedException;
use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    use ParsesMiddlewareParameters;

    public function handle(Request $request, Closure $next, string ...$permissions): mixed
    {
        $user = $request->user();

        abort_unless($user instanceof Roleable, 401, 'Unauthenticated.');

        $flattenedPermissions = $this->parseParameters($permissions);

        if (collect($flattenedPermissions)->doesntContain(fn ($p) => $user->hasPermission($p))) {
            throw PermissionDeniedException::create(implode(', ', $flattenedPermissions));
        }

        return $next($request);
    }
}
