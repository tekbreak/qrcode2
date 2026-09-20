<?php

namespace Tests\Feature\Security;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Settings\SettingsIndex;
use App\Models\User;
use App\Services\SignupService;
use Database\Seeders\MockUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login|victim@example.com|127.0.0.1');
    }

    public function test_login_locks_out_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@example.com',
            'password' => 'correct-horse-battery',
        ]);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors('email');
        }

        // Even the correct password is refused while the lockout holds.
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'correct-horse-battery')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_successful_login_clears_the_throttle(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@example.com',
            'password' => 'correct-horse-battery',
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)->set('password', 'wrong')->call('login');

        Livewire::test(Login::class)
            ->set('email', $user->email)->set('password', 'correct-horse-battery')->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse(RateLimiter::tooManyAttempts('login|victim@example.com|127.0.0.1', 1));
    }

    public function test_password_reset_requests_are_throttled(): void
    {
        $user = User::factory()->create(['email' => 'victim@example.com']);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(ForgotPassword::class)
                ->set('email', $user->email)->call('sendResetLink');
        }

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)->call('sendResetLink')
            ->assertHasErrors('email');
    }

    public function test_magic_link_requests_are_throttled(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('auth.magic-link.send'), ['email' => $user->email])->assertRedirect();
        }

        $this->post(route('auth.magic-link.send'), ['email' => $user->email])
            ->assertStatus(429);
    }

    public function test_quick_login_is_unavailable_outside_local_and_testing(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.dev_quick_login' => true]);   // even with the flag forced on

        Livewire::test(Login::class)
            ->call('quickLogin', 'admin@example.com')
            ->assertStatus(404);

        $this->assertGuest();
    }

    public function test_the_mock_user_seeder_refuses_to_run_outside_local(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(\RuntimeException::class);

        try {
            (new MockUserSeeder)->run();
        } finally {
            $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        }
    }

    public function test_unverified_users_cannot_reach_publishing_surfaces(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/qr-codes/create')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get('/qr-codes')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));

        // Billing and settings stay reachable so the address can still be fixed.
        $this->actingAs($user)->get('/settings')->assertOk();
    }

    public function test_account_deletion_requires_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse-battery']);

        Livewire::actingAs($user)->test(SettingsIndex::class)
            ->set('delete_password', 'wrong')
            ->call('deleteAccount')
            ->assertHasErrors('delete_password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_account_deletion_with_the_correct_password_removes_tokens_and_sessions(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse-battery']);
        $user->createToken('api');

        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);

        Livewire::actingAs($user)->test(SettingsIndex::class)
            ->set('delete_password', 'correct-horse-battery')
            ->call('deleteAccount');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_a_password_change_revokes_api_tokens(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse-battery']);
        $user->createToken('api');

        Livewire::actingAs($user)->test(SettingsIndex::class)
            ->set('current_password', 'correct-horse-battery')
            ->set('new_password', 'a-new-strong-password-1')
            ->set('new_password_confirmation', 'a-new-strong-password-1')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertTrue(Hash::check('a-new-strong-password-1', $user->fresh()->password));
    }

    public function test_a_pending_signup_never_holds_a_plaintext_password(): void
    {
        app(SignupService::class)->storeEmailSignup([
            'name' => 'Test', 'email' => 'new@example.com', 'password' => 'super-secret-value',
        ]);

        $pending = session(SignupService::SESSION_KEY);

        $this->assertNotSame('super-secret-value', $pending['password']);
        $this->assertTrue(Hash::check('super-secret-value', $pending['password']));
    }

    public function test_a_user_created_from_a_pending_signup_can_log_in(): void
    {
        app(SignupService::class)->storeEmailSignup([
            'name' => 'Test', 'email' => 'new@example.com', 'password' => 'super-secret-value',
        ]);

        $user = app(SignupService::class)->createUserFromPendingSignup();

        $this->assertTrue(Hash::check('super-secret-value', $user->password));
        $this->assertTrue(auth()->attempt(['email' => 'new@example.com', 'password' => 'super-secret-value']));
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Content-Security-Policy');   // report-only by default

        $this->assertNotNull($this->get('/')->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_slugs_have_enough_entropy_to_resist_enumeration(): void
    {
        $slug = \App\Models\ShortLink::generateSlug();

        $this->assertGreaterThanOrEqual(10, strlen($slug));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $slug);
    }
}
