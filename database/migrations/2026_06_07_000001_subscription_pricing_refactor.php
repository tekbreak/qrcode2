<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_transactions')) {
            Schema::dropIfExists('credit_transactions');
        }

        if (Schema::hasTable('credit_balances')) {
            Schema::dropIfExists('credit_balances');
        }

        if (Schema::hasColumn('plans', 'monthly_credits')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('monthly_credits');
            });
        }

        if (Schema::hasTable('plans')) {
            DB::table('plans')->where('slug', 'free')->update([
                'slug' => 'starter',
                'name' => 'Starter',
                'description' => 'Get started with QR codes',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_static_qr' => 5,
                'max_dynamic_qr' => 1,
                'features' => json_encode(['export_png', 'basic_customization', 'basic_analytics']),
                'sort_order' => 0,
            ]);

            DB::table('plans')
                ->where('slug', 'starter')
                ->where('price_monthly', '>', 0)
                ->update(['is_active' => false]);

            DB::table('plans')->where('slug', 'pro')->update([
                'price_monthly' => 1000,
                'price_yearly' => 0,
                'max_static_qr' => null,
                'max_dynamic_qr' => 10,
                'features' => json_encode([
                    'export_png', 'export_jpg', 'export_svg', 'export_eps',
                    'basic_customization', 'full_customization',
                    'basic_analytics', 'advanced_analytics',
                    'api_access', 'bulk_operations',
                ]),
                'sort_order' => 1,
            ]);

            DB::table('plans')->where('slug', 'enterprise')->update([
                'price_monthly' => 3900,
                'price_yearly' => 38900,
                'max_static_qr' => null,
                'max_dynamic_qr' => null,
                'sort_order' => 2,
            ]);
        }

        Schema::create('paid_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type');
            $table->string('status')->default('pending');
            $table->string('stripe_checkout_session_id')->nullable();
            $table->json('pending_data');
            $table->unsignedInteger('amount_cents')->default(100);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paid_actions');
    }
};
