<?php

return [
    'enabled_languages' => ['en', 'es'],

    'proxy_scheme' => env('PROXY_SCHEME', 'https'),

    'paid_action_price_cents' => 100,

    'paid_action_stripe_price_id' => env('STRIPE_PAID_ACTION_PRICE_ID'),

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_static_qr' => 5,
            'max_dynamic_qr' => 1,
            'features' => ['export_png', 'basic_customization', 'basic_analytics'],
        ],
        'pro' => [
            'name' => 'Pro',
            'price_monthly' => 1000,
            'price_yearly' => 9900,
            'max_static_qr' => null,
            'max_dynamic_qr' => 10,
            'features' => [
                'export_png', 'export_jpg', 'export_svg', 'export_eps',
                'basic_customization', 'full_customization',
                'basic_analytics', 'advanced_analytics',
                'api_access', 'bulk_operations',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_monthly' => 3900,
            'price_yearly' => 38900,
            'max_static_qr' => null,
            'max_dynamic_qr' => null,
            'features' => [
                'export_png', 'export_jpg', 'export_svg', 'export_eps',
                'basic_customization', 'full_customization',
                'basic_analytics', 'advanced_analytics',
                'custom_domains', 'api_access', 'bulk_operations',
                'teams', 'priority_support',
            ],
        ],
    ],

    'signup_trial_days' => 30,

    'slug_length' => 7,
    'logo_max_size' => 2048,
    'logo_max_dimension' => 500,
];
