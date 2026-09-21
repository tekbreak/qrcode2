<?php

namespace Tests\Feature\Seo;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PageMetadataTest extends TestCase
{
    use RefreshDatabase;

    private function site(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    public function test_landing_page_is_canonical_and_indexable(): void
    {
        $response = $this->get($this->site('/'));

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.$this->site('/').'">', false);
        $response->assertSee('name="robots" content="index, follow, max-image-preview:large"', false);
        $response->assertDontSee('content="noindex', false);
    }

    public function test_landing_page_carries_social_card_tags(): void
    {
        $response = $this->get($this->site('/'));

        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:image" content="'.asset('images/og-image.png').'"', false);
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
    }

    public function test_canonical_ignores_the_request_host(): void
    {
        // A crawler arriving on www must still be told the bare-domain URL.
        $response = $this->get('http://www.'.parse_url((string) config('app.url'), PHP_URL_HOST).'/');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.$this->site('/').'">', false);
    }

    public function test_landing_page_emits_valid_structured_data(): void
    {
        $content = $this->get($this->site('/'))->getContent();

        $this->assertMatchesRegularExpression(
            '#<script type="application/ld\+json">(.+?)</script>#s',
            $content,
        );

        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $content, $matches);
        $data = json_decode($matches[1], true);

        $this->assertIsArray($data, 'JSON-LD block is not valid JSON.');
        $this->assertSame('https://schema.org', $data['@context']);

        $types = array_column($data['@graph'], '@type');
        $this->assertContains('Organization', $types);
        $this->assertContains('SoftwareApplication', $types);
        $this->assertContains('FAQPage', $types);

        $app = $data['@graph'][array_search('SoftwareApplication', $types, true)];
        $this->assertCount(3, $app['offers']);
        $this->assertSame('EUR', $app['offers'][0]['priceCurrency']);

        // The free tier still needs an explicit "0" - an Offer without a price
        // is invalid, and a falsy-value filter is an easy way to drop it.
        $this->assertSame('Starter', $app['offers'][0]['name']);
        $this->assertSame('0', $app['offers'][0]['price']);
        $this->assertSame('10', $app['offers'][1]['price']);

        // Fabricated ratings are a manual-action risk, so there must be none.
        $this->assertArrayNotHasKey('aggregateRating', $app);
    }

    public function test_structured_data_answers_match_the_visible_faq(): void
    {
        $content = $this->get($this->site('/'))->getContent();

        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $content, $matches);
        $graph = json_decode($matches[1], true)['@graph'];
        $types = array_column($graph, '@type');
        $faq = $graph[array_search('FAQPage', $types, true)];

        $this->assertCount(6, $faq['mainEntity']);
        $this->assertSame(__('landing.faq.items.dynamic.q'), $faq['mainEntity'][0]['name']);
        $this->assertSame(
            __('landing.faq.items.dynamic.a'),
            $faq['mainEntity'][0]['acceptedAnswer']['text'],
        );
    }

    public function test_authenticated_pages_are_noindex(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get($this->site('/settings'));

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex, nofollow"', false);
        $response->assertDontSee('rel="canonical"', false);
    }

    #[DataProvider('appPaths')]
    public function test_app_pages_are_noindex_and_carry_no_metadata(string $path): void
    {
        $response = $this->get($this->site($path));

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex, nofollow"', false);
        $response->assertDontSee('rel="canonical"', false);
        $response->assertDontSee('property="og:', false);
        $response->assertDontSee('name="twitter:', false);
        $response->assertDontSee('name="description"', false);
    }

    public static function appPaths(): array
    {
        return [
            'login' => ['/login'],
            'register' => ['/register'],
            'forgot password' => ['/forgot-password'],
            'password reset' => ['/reset-password/some-token'],
        ];
    }

    public function test_the_plan_picker_is_noindex(): void
    {
        // Renders on its own wide layout, and only once a signup is under way.
        $user = User::factory()->create(['plan_selected_at' => null]);

        $this->actingAs($user)
            ->get($this->site('/choose-plan'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->assertDontSee('rel="canonical"', false);
    }

    public function test_only_the_landing_page_is_indexable(): void
    {
        $this->get($this->site('/'))
            ->assertSee('content="index, follow, max-image-preview:large"', false);

        $this->get($this->site('/register'))
            ->assertDontSee('content="index', false);
    }

    public function test_app_pages_still_get_icons(): void
    {
        $this->get($this->site('/login'))
            ->assertSee('rel="icon"', false)
            ->assertSee('rel="apple-touch-icon"', false);
    }
}
