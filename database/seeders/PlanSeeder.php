<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::where('slug', 'free')->delete();
        Plan::where('slug', 'starter')->where('price_monthly', '>', 0)->delete();

        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'description' => 'Get started with QR codes',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
                'max_static_qr' => 5,
                'max_dynamic_qr' => 1,
                'features' => ['export_png', 'basic_customization', 'basic_analytics'],
                'sort_order' => 0,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'description' => 'For growing businesses',
                'price_monthly' => 1000,
                'price_yearly' => 9900,
                'stripe_monthly_price_id' => env('STRIPE_PRO_MONTHLY_PRICE_ID', 'dev_pro_monthly'),
                'stripe_yearly_price_id' => env('STRIPE_PRO_YEARLY_PRICE_ID', 'dev_pro_yearly'),
                'max_static_qr' => null,
                'max_dynamic_qr' => 10,
                'features' => [
                    'export_png', 'export_jpg', 'export_svg', 'export_eps',
                    'basic_customization', 'full_customization',
                    'basic_analytics', 'advanced_analytics',
                    'api_access', 'bulk_operations',
                ],
                'sort_order' => 1,
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'For large teams and agencies',
                'price_monthly' => 3900,
                'price_yearly' => 38900,
                'stripe_monthly_price_id' => env('STRIPE_ENTERPRISE_MONTHLY_PRICE_ID', 'dev_enterprise_monthly'),
                'stripe_yearly_price_id' => env('STRIPE_ENTERPRISE_YEARLY_PRICE_ID', 'dev_enterprise_yearly'),
                'max_static_qr' => null,
                'max_dynamic_qr' => null,
                'features' => [
                    'export_png', 'export_jpg', 'export_svg', 'export_eps',
                    'basic_customization', 'full_customization',
                    'basic_analytics', 'advanced_analytics',
                    'custom_domains', 'api_access', 'bulk_operations',
                    'teams', 'priority_support',
                ],
                'sort_order' => 2,
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
