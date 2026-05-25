<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free',
                'name' => 'Free',
                'description' => 'Get started with QR codes',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
                'monthly_credits' => 5,
                'max_static_qr' => 3,
                'max_dynamic_qr' => null,
                'features' => ['export_png', 'basic_customization', 'basic_analytics'],
                'sort_order' => 0,
            ],
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'description' => 'For individuals and small projects',
                'price_monthly' => 500,
                'price_yearly' => 5000,
                'stripe_monthly_price_id' => env('STRIPE_STARTER_MONTHLY_PRICE_ID', 'dev_starter_monthly'),
                'stripe_yearly_price_id' => env('STRIPE_STARTER_YEARLY_PRICE_ID', 'dev_starter_yearly'),
                'monthly_credits' => 50,
                'max_static_qr' => 10,
                'max_dynamic_qr' => null,
                'features' => ['export_png', 'export_jpg', 'basic_customization', 'basic_analytics', 'advanced_analytics'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'description' => 'For growing businesses',
                'price_monthly' => 1500,
                'price_yearly' => 15000,
                'stripe_monthly_price_id' => env('STRIPE_PRO_MONTHLY_PRICE_ID', 'dev_pro_monthly'),
                'stripe_yearly_price_id' => env('STRIPE_PRO_YEARLY_PRICE_ID', 'dev_pro_yearly'),
                'monthly_credits' => 200,
                'max_static_qr' => 50,
                'max_dynamic_qr' => null,
                'features' => ['export_png', 'export_jpg', 'export_svg', 'export_eps', 'basic_customization', 'full_customization', 'basic_analytics', 'advanced_analytics', 'custom_domains', 'api_access'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'For large teams and agencies',
                'price_monthly' => 5000,
                'price_yearly' => 50000,
                'stripe_monthly_price_id' => env('STRIPE_ENTERPRISE_MONTHLY_PRICE_ID', 'dev_enterprise_monthly'),
                'stripe_yearly_price_id' => env('STRIPE_ENTERPRISE_YEARLY_PRICE_ID', 'dev_enterprise_yearly'),
                'monthly_credits' => 0,
                'max_static_qr' => null,
                'max_dynamic_qr' => null,
                'features' => ['export_png', 'export_jpg', 'export_svg', 'export_eps', 'basic_customization', 'full_customization', 'basic_analytics', 'advanced_analytics', 'custom_domains', 'api_access', 'bulk_operations', 'teams', 'priority_support'],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
