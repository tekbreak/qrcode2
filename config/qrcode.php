<?php

return [
    'proxy_scheme' => env('PROXY_SCHEME', 'https'),

    'credits' => [
        'dynamic_qr_maintenance' => 5,
        'edit_dynamic_qr' => 5,
        'premium_customization' => 2,
        'svg_download' => 1,
        'eps_download' => 3,
        'bulk_generation' => 3,
        'api_call' => 5,
        'analytics_export' => 5,
        'scans_per_credit' => 1000,
    ],

    'plans' => [
        'free' => [
            'name' => 'Free',
            'monthly_credits' => 5,
            'max_static_qr' => 3,
            'max_dynamic_qr' => null,
            'features' => ['export_png', 'basic_customization', 'basic_analytics'],
        ],
        'starter' => [
            'name' => 'Starter',
            'price_monthly' => 500,
            'price_yearly' => 5000,
            'monthly_credits' => 50,
            'max_static_qr' => 10,
            'max_dynamic_qr' => null,
            'features' => ['export_png', 'export_jpg', 'basic_customization', 'basic_analytics', 'advanced_analytics'],
        ],
        'pro' => [
            'name' => 'Pro',
            'price_monthly' => 1500,
            'price_yearly' => 15000,
            'monthly_credits' => 200,
            'max_static_qr' => 50,
            'max_dynamic_qr' => null,
            'features' => ['export_png', 'export_jpg', 'export_svg', 'export_eps', 'basic_customization', 'full_customization', 'basic_analytics', 'advanced_analytics', 'custom_domains', 'api_access'],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_monthly' => 5000,
            'price_yearly' => 50000,
            'monthly_credits' => 0,
            'max_static_qr' => null,
            'max_dynamic_qr' => null,
            'unlimited_credits' => true,
            'features' => ['export_png', 'export_jpg', 'export_svg', 'export_eps', 'basic_customization', 'full_customization', 'basic_analytics', 'advanced_analytics', 'custom_domains', 'api_access', 'bulk_operations', 'teams', 'priority_support'],
        ],
    ],

    'credit_packs' => [
        [
            'slug' => 'pack-10',
            'credits' => 10,
            'price' => 200,
            'label' => '10 credits',
            'description' => 'Maintain 2 dynamic QRs for a month',
            'stripe_price_id' => env('STRIPE_CREDIT_PACK_10_PRICE_ID'),
        ],
        [
            'slug' => 'pack-50',
            'credits' => 50,
            'price' => 800,
            'label' => '50 credits',
            'description' => 'Maintain 10 dynamic QRs for a month',
            'stripe_price_id' => env('STRIPE_CREDIT_PACK_50_PRICE_ID'),
            'popular' => true,
        ],
        [
            'slug' => 'pack-150',
            'credits' => 150,
            'price' => 2000,
            'label' => '150 credits',
            'description' => 'Maintain 30 dynamic QRs for a month',
            'stripe_price_id' => env('STRIPE_CREDIT_PACK_150_PRICE_ID'),
        ],
        [
            'slug' => 'pack-500',
            'credits' => 500,
            'price' => 5000,
            'label' => '500 credits',
            'description' => 'Maintain 100 dynamic QRs for a month',
            'stripe_price_id' => env('STRIPE_CREDIT_PACK_500_PRICE_ID'),
        ],
    ],

    'slug_length' => 7,
    'logo_max_size' => 2048,
    'logo_max_dimension' => 500,
];
