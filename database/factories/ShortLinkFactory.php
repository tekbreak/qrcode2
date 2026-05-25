<?php

namespace Database\Factories;

use App\Models\QrCode;
use App\Models\ShortLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShortLink>
 */
class ShortLinkFactory extends Factory
{
    protected $model = ShortLink::class;

    public function definition(): array
    {
        return [
            'qr_code_id' => QrCode::factory(),
            'slug' => Str::random(7),
            'destination_url' => fake()->url(),
            'is_active' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function passwordProtected(string $password = 'secret'): static
    {
        return $this->state(fn () => [
            'password_hash' => bcrypt($password),
        ]);
    }

    public function maxScans(int $max): static
    {
        return $this->state(fn () => [
            'max_scans' => $max,
        ]);
    }
}
