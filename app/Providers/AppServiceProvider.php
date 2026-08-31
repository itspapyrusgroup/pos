<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('sync-api', function (Request $request) {
            $maxPerMinute = max(1, (int) config('sync.rate_limit_per_minute', 60));

            return [
                Limit::perMinute($maxPerMinute)->by((string) $request->ip()),
            ];
        });

        Paginator::useBootstrapFive();
    }
}
