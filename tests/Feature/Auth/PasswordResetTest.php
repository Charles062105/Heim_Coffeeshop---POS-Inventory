<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_email_password_recovery_is_unavailable(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => $user->email])->assertNotFound();
        $this->get('/reset-password/any-token')->assertNotFound();
        $this->post('/reset-password', [
            'token' => 'any-token',
            'email' => $user->email,
            'password' => 'CoffeeShop123!',
            'password_confirmation' => 'CoffeeShop123!',
        ])->assertNotFound();

        Notification::assertNothingSent();
        $this->assertFalse(Hash::check('CoffeeShop123!', $user->fresh()->password));
    }

    public function test_login_instructs_users_to_contact_the_owner_for_password_reset(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Contact the Store Owner.')
            ->assertDontSee('Forgot password? No problem.');
    }
}
