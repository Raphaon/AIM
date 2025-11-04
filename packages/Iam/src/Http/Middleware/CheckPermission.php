<?php

namespace Aim\Iam\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user(config('iam.api_guard', 'sanctum'));

        if (! $user || ! method_exists($user, 'hasPermission')) {
            abort(403, 'User permission verification unavailable.');
        }

        foreach ($permissions as $permission) {
            if (! $user->hasPermission($permission)) {
                abort(403, 'User does not have the required permission.');
            }
        }

        return $next($request);
    }
}
