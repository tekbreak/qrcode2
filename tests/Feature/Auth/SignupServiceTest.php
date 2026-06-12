<?php

namespace Tests\Feature\Auth;

use App\Services\SignupService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
    }

    public function test_complete_signup_starter_creates_user(): void
    {
        $this->withSession([
            SignupService::SESSION_KEY => [
                'type' => 'email',
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
            ],
        ]);

        $result = app(SignupService::class)->completeSignup('starter');

        $this->assertSame(route('dashboard', ['welcome' => 1]), $result['redirect']->getTargetUrl());
        $this->assertSame('jane@example.com', $result['user']->email);
    }
}
