<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_the_login_page(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in to SOIMS');
    }

    public function test_login_form_uses_forwarded_https_scheme_from_local_proxy(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'soims.example.test',
            ])
            ->get('/login')
            ->assertOk()
            ->assertSee('action="https://soims.example.test/login"', false);
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->post('/login', [
            'identifier' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->from('/login')->post('/login', [
            'identifier' => $user->username,
            'password' => 'incorrect',
        ])->assertRedirect('/login')->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => false]);

        $this->from('/login')->post('/login', [
            'identifier' => $user->username,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_protected_session_page_redirects_guests(): void
    {
        $this->get('/account/session')->assertRedirect(route('login'));
    }

    public function test_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_attempts_are_throttled(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        foreach (range(1, 5) as $attempt) {
            $this->from('/login')->post('/login', [
                'identifier' => $user->username,
                'password' => 'incorrect',
            ])->assertRedirect('/login');
        }

        $this->from('/login')->post('/login', [
            'identifier' => $user->username,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('identifier');

        RateLimiter::clear(strtolower($user->username).'|127.0.0.1');
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->from('/account/password')->put('/account/password', [
            'current_password' => 'incorrect',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/account/password')->assertSessionHasErrors('current_password');

        $this->assertAuthenticatedAs($user);
    }
}
