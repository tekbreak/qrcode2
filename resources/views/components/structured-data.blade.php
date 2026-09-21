@props([
    'plans' => [],
    'faqKeys' => [],
])

@php
    $siteUrl = rtrim((string) config('app.url'), '/');
    $name = config('app.name');

    $graph = [
        [
            '@type' => 'Organization',
            '@id' => $siteUrl.'/#organization',
            'name' => $name,
            'url' => $siteUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('images/logo-512.png'),
                'width' => 512,
                'height' => 512,
            ],
        ],
        [
            '@type' => 'WebSite',
            '@id' => $siteUrl.'/#website',
            'name' => $name,
            'url' => $siteUrl,
            'publisher' => ['@id' => $siteUrl.'/#organization'],
            'inLanguage' => app()->getLocale(),
        ],
        [
            '@type' => 'SoftwareApplication',
            '@id' => $siteUrl.'/#app',
            'name' => $name,
            'url' => $siteUrl,
            'description' => __('landing.meta_description'),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'publisher' => ['@id' => $siteUrl.'/#organization'],
            // No aggregateRating: there are no real reviews to cite, and
            // inventing one is exactly what earns a manual action.
            'offers' => collect($plans)->map(fn (array $plan) => [
                '@type' => 'Offer',
                'name' => $plan['tier']->label(),
                'price' => (string) $plan['monthly'],
                'priceCurrency' => 'EUR',
                'url' => route('register'),
                'availability' => 'https://schema.org/InStock',
            ])->values()->all(),
        ],
    ];

    if ($faqKeys !== []) {
        $graph[] = [
            '@type' => 'FAQPage',
            '@id' => $siteUrl.'/#faq',
            'mainEntity' => collect($faqKeys)->map(fn (string $key) => [
                '@type' => 'Question',
                'name' => __('landing.faq.items.'.$key.'.q'),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __('landing.faq.items.'.$key.'.a'),
                ],
            ])->values()->all(),
        ];
    }
@endphp

<script type="application/ld+json">@json(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)</script>
