<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        // QR rendering walks every pixel and may shell out to ImageMagick, so it
        // gets a tighter budget than the rest of the API.
        RateLimiter::for('qr-render', fn (Request $request) => Limit::perMinute(20)
            ->by($request->user()?->id ?: $request->ip()));

        // Short links are the public scan surface: generous for real traffic,
        // but enough to make slug enumeration impractical.
        RateLimiter::for('redirect', fn (Request $request) => Limit::perMinute(120)
            ->by($request->ip()));

        RateLimiter::for('link-unlock', fn (Request $request) => Limit::perMinute(10)
            ->by($request->ip()));
    }
}
