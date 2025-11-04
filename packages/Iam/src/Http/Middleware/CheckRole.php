<?php

namespace Aim\Iam\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user(config('iam.api_guard', 'sanctum'));

        if (! $user || ! method_exists($user, 'hasRole')) {
            abort(403, 'User role verification unavailable.');
        }

        if (! $user->hasAnyRole($roles)) {
            abort(403, 'User does not have the required role.');
        }

        return $next($request);
    }
}
