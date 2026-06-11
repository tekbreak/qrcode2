<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? $request->cookie('locale')
            ?? $request->getPreferredLanguage(['en', 'es'])
            ?? 'en';

        if (in_array($locale, ['en', 'es'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
