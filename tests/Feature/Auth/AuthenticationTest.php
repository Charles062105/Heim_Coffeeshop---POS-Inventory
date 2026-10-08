<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_authenticated_layout_renders_logout_confirmation_modal_and_handlers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="logout-modal"', false);
        $response->assertSee('Confirm Log Out');
        $response->assertSee('confirmLogout');
        $response->assertSee('id="logout-cancel-btn"', false);
        $response->assertSee('id="logout-confirm-btn"', false);
        $response->assertSee('id="header-logout-form"', false);
        $response->assertSee('@click="sidebarOpen = false" class="flex items-center justify-between border-b border-slate-200/80 px-4 py-3.5"', false);
    }

    public function test_login_screen_renders_polished_elements_without_demo_accounts(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('id="login-form"', false);
        $response->assertSee('id="toggle-password-btn"', false);
        $response->assertSee('togglePasswordVisibility');
        $response->assertSee('id="login-submit-btn"', false);
        $response->assertDontSee('quickFill');
        $response->assertDontSee('Demo accounts');
        $response->assertDontSee('owner@coffee.com');
        $response->assertDontSee('manager@coffee.com');
        $response->assertDontSee('cashier@coffee.com');
    }

    public function test_users_can_authenticate_with_whitespace_in_email(): void
    {
        $user = User::factory()->create([
            'email' => 'trimmed.user@coffee.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => '   trimmed.user@coffee.com   ',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_archived_user_cannot_authenticate_with_valid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive.user@coffee.com',
            'password' => 'password',
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'email' => 'Your account is archived. Please contact store management or the owner.',
        ]);
    }

    public function test_cashier_is_redirected_to_pos_terminal_after_login(): void
    {
        $cashier = User::factory()->create([
            'email' => 'cashier.test@coffee.com',
            'password' => 'password',
            'role' => 'cashier',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $cashier->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('pos.index', absolute: false));
    }
}
