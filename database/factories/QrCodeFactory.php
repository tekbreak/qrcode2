<?php

namespace Database\Factories;

use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrCode>
 */
class QrCodeFactory extends Factory
{
    protected $model = QrCode::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'type' => QrCodeType::Url,
            'is_dynamic' => false,
            'content_data' => ['url' => fake()->url()],
            'status' => 'active',
            'total_scans' => 0,
        ];
    }

    public function dynamic(): static
    {
        return $this->state(fn () => [
            'is_dynamic' => true,
        ]);
    }

    public function text(): static
    {
        return $this->state(fn () => [
            'type' => QrCodeType::Text,
            'content_data' => ['text' => fake()->sentence()],
        ]);
    }
}
