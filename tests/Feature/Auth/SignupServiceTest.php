<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SignupService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_oauth_signup_creates_an_already_verified_user(): void
    {
        $this->withSession([
            SignupService::SESSION_KEY => [
                'type' => 'oauth',
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'google_id' => '1234567890',
                'avatar' => 'https://example.com/avatar.png',
            ],
        ]);

        $user = app(SignupService::class)->createUserFromPendingSignup();

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame('1234567890', $user->fresh()->google_id);
    }

    public function test_an_oauth_signup_cannot_claim_a_pre_existing_account(): void
    {
        $victim = User::factory()->create([
            'email' => 'jane@example.com',
            'google_id' => null,
        ]);

        $this->withSession([
            SignupService::SESSION_KEY => [
                'type' => 'oauth',
                'name' => 'Not Jane',
                'email' => 'jane@example.com',
                'google_id' => '1234567890',
                'avatar' => null,
            ],
        ]);

        $this->expectException(\RuntimeException::class);

        try {
            app(SignupService::class)->createUserFromPendingSignup();
        } finally {
            $this->assertNull($victim->fresh()->google_id);
            $this->assertGuest();
        }
    }

    public function test_an_email_signup_cannot_claim_a_pre_existing_account(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'the-real-owners-password',
        ]);

        $this->withSession([
            SignupService::SESSION_KEY => [
                'type' => 'email',
                'name' => 'Not Jane',
                'email' => 'jane@example.com',
                'password' => Hash::make('an-attackers-password'),
            ],
        ]);

        $this->expectException(\RuntimeException::class);

        app(SignupService::class)->createUserFromPendingSignup();
    }

    public function test_resubmitting_the_same_signup_returns_the_row_it_created(): void
    {
        $this->withSession([
            SignupService::SESSION_KEY => [
                'type' => 'oauth',
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'google_id' => '1234567890',
                'avatar' => null,
            ],
        ]);

        $signup = app(SignupService::class);
        $first = $signup->createUserFromPendingSignup();
        $second = $signup->createUserFromPendingSignup();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, User::where('email', 'jane@example.com')->count());
    }

    public function test_resubmitting_the_same_email_signup_returns_the_row_it_created(): void
    {
        app(SignupService::class)->storeEmailSignup([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'super-secret-value',
        ]);

        $signup = app(SignupService::class);
        $first = $signup->createUserFromPendingSignup();
        $second = $signup->createUserFromPendingSignup();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, User::where('email', 'jane@example.com')->count());
    }

    public function test_email_signup_creates_an_unverified_user(): void
    {
        $this->withSession([
            SignupService::SESSION_KEY => [
                'type' => 'email',
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'hashed-password',
            ],
        ]);

        $user = app(SignupService::class)->createUserFromPendingSignup();

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
