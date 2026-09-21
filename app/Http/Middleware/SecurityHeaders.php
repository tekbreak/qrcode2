<?php

namespace App\Http\Middleware;

use App\Support\Url;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // The short-link domains must never be indexed: a crawl of a slug fires
        // RecordScanJob, so bot traffic would land in a customer's analytics.
        // robots.txt alone would not stop an already-linked slug being indexed.
        if (! Url::isCanonicalHost($request->getHost())) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        // Routes that set their own (the short-link pages use no-referrer) win.
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $this->applyContentSecurityPolicy($response);

        return $response;
    }

    /**
     * Reported by default, enforced when CSP_ENFORCE=true.
     *
     * The policy still needs 'unsafe-inline' and 'unsafe-eval' because the
     * layouts carry inline scripts and Alpine evaluates expressions at runtime.
     * Report-only first so those can be removed against real traffic before the
     * policy is switched to enforcing.
     */
    protected function applyContentSecurityPolicy(Response $response): void
    {
        $policy = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);

        $header = config('app.csp_enforce')
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        if (! $response->headers->has($header)) {
            $response->headers->set($header, $policy);
        }
    }
}
