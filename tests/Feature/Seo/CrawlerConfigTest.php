<?php

namespace Tests\Feature\Seo;

use Tests\TestCase;

class CrawlerConfigTest extends TestCase
{
    private function site(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    private function proxy(string $path): string
    {
        return 'http://'.config('app.proxy_domain').$path;
    }

    public function test_site_robots_allows_crawling_and_points_at_the_sitemap(): void
    {
        $response = $this->get($this->site('/robots.txt'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('Allow: /', $response->getContent());
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $response->getContent());
    }

    public function test_site_robots_blocks_authenticated_and_token_paths(): void
    {
        $body = $this->get($this->site('/robots.txt'))->getContent();

        foreach (['/admin', '/dashboard', '/billing', '/settings', '/reset-password', '/auth/', '/up'] as $path) {
            $this->assertStringContainsString('Disallow: '.$path, $body);
        }
    }

    public function test_site_robots_still_allows_the_noindex_app_pages(): void
    {
        // Googlebot must be able to fetch these to read their noindex tag.
        $body = $this->get($this->site('/robots.txt'))->getContent();

        foreach (['/login', '/register', '/forgot-password'] as $path) {
            $this->assertStringNotContainsString('Disallow: '.$path, $body);
        }
    }

    public function test_proxy_domain_robots_blocks_everything(): void
    {
        $response = $this->get($this->proxy('/robots.txt'));

        $response->assertOk();
        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
    }

    public function test_proxy_domain_responses_are_noindex(): void
    {
        $this->get($this->proxy('/robots.txt'))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_site_responses_are_not_noindex(): void
    {
        $this->get($this->site('/'))
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_sitemap_lists_the_public_pages(): void
    {
        $response = $this->get($this->site('/sitemap.xml'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $body = $response->getContent();
        $this->assertStringContainsString('<loc>'.route('landing').'</loc>', $body);

        // The marketing page is the only thing being offered to search.
        $this->assertSame(1, substr_count($body, '<loc>'));
        $this->assertStringNotContainsString(route('register'), $body);
        $this->assertStringNotContainsString(route('dashboard'), $body);

        $this->assertNotFalse(simplexml_load_string($body), 'Sitemap is not well-formed XML.');
    }

    public function test_sitemap_is_not_served_on_the_proxy_domain(): void
    {
        $this->get($this->proxy('/sitemap.xml'))->assertNotFound();
    }
}
