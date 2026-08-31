<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictLocalMasterAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((string) config('sync.app_mode') !== 'local_branch') {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        $blockedPatterns = (array) config('sync.local_blocked_route_names', []);
        foreach ($blockedPatterns as $pattern) {
            if ($pattern === '') {
                continue;
            }
            if (str_ends_with($pattern, '*')) {
                $prefix = substr($pattern, 0, -1);
                if ($prefix !== '' && str_starts_with($routeName, $prefix)) {
                    abort(403, 'Master data hanya dapat dikelola dari cloud center.');
                }
            } elseif ($routeName === $pattern) {
                abort(403, 'Master data hanya dapat dikelola dari cloud center.');
            }
        }

        return $next($request);
    }
}
