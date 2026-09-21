<?php

namespace App\Http\Controllers;

use App\Support\Url;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Crawler-facing files. These are served from routes rather than public/ because
 * the short-link proxy domain shares the document root: a static robots.txt
 * would hand the same permissive rules to both hosts, and every crawl of
 * go.<domain>/{slug} dispatches a RecordScanJob into a customer's analytics.
 */
class SeoController extends Controller
{
    /**
     * Paths not worth a crawl at all. Prefix matches, so /qr-codes also covers
     * /qr-codes/create.
     *
     * The publicly reachable app pages - /login, /register, /forgot-password -
     * are deliberately absent: they carry a noindex tag, and Googlebot has to
     * be allowed to fetch them to ever see it. Blocking them here would leave
     * them eligible to surface as bare URLs instead.
     */
    protected const DISALLOWED = [
        '/admin',
        '/dashboard',
        '/qr-codes',
        '/analytics',
        '/teams',
        '/billing',
        '/settings',
        '/choose-plan',
        '/email/verify',
        '/reset-password',
        '/auth/',
        '/up',
    ];

    /**
     * The marketing page is the only thing meant to rank. Every app surface,
     * signup included, is noindex, so nothing else belongs here.
     */
    protected const SITEMAP_ROUTES = ['landing'];

    public function robots(Request $request): Response
    {
        $body = Url::isCanonicalHost($request->getHost())
            ? $this->siteRobots()
            : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(Request $request): Response
    {
        if (! Url::isCanonicalHost($request->getHost())) {
            abort(404);
        }

        $lastModified = $this->lastModified();

        $urls = collect(self::SITEMAP_ROUTES)
            ->map(fn (string $name) => '    <url>'."\n"
                .'        <loc>'.e(route($name)).'</loc>'."\n"
                .'        <lastmod>'.$lastModified.'</lastmod>'."\n"
                .'    </url>')
            ->implode("\n");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$urls."\n"
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    protected function siteRobots(): string
    {
        $lines = ['User-agent: *', 'Allow: /'];

        foreach (self::DISALLOWED as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return implode("\n", $lines)."\n";
    }

    /**
     * The landing view's mtime, which on a deployed release is the deploy time.
     * changefreq and priority are omitted: Google ignores both.
     */
    protected function lastModified(): string
    {
        $view = resource_path('views/landing/index.blade.php');
        $timestamp = is_file($view) ? filemtime($view) : false;

        return date('Y-m-d', $timestamp ?: time());
    }
}
