<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return $next($request);
        }

        $permission = $this->resolvePermissionByRoute($routeName);
        if (!$permission) {
            abort(403, 'Akses route ditolak karena permission belum dipetakan.');
        }

        if (!$user->hasPermission($permission)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses modul ini.');
        }

        return $next($request);
    }

    private function resolvePermissionByRoute(string $routeName): ?string
    {
        $map = config('rbac.route_permission_map', []);
        $direct = Arr::get($map, $routeName);
        if ($direct) {
            return $direct;
        }

        foreach ($map as $pattern => $permission) {
            if (Str::contains($pattern, '*') && Str::is($pattern, $routeName)) {
                return $permission;
            }
        }

        return null;
    }
}
