<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSyncIpAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = (array) config('sync.allowed_ips', []);
        if (empty($allowedIps)) {
            return $next($request);
        }

        $ip = (string) $request->ip();
        if (!in_array($ip, $allowedIps, true)) {
            return new JsonResponse(['message' => 'Forbidden source IP'], 403);
        }

        return $next($request);
    }
}
