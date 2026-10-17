<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        abort_unless($request->user()?->hasPermission($permissionKey), 403);

        return $next($request);
    }
}
