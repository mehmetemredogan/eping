<?php

namespace Tests\Feature\Auth;

use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_with_valid_captcha(): void
    {
        Session::put(CaptchaService::SESSION_KEY, 'ABC42');

        $response = $this->post('/register', [
            'username' => 'test_user',
            'password' => 'password',
            'password_confirmation' => 'password',
            'captcha' => 'abc42',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('history.index', absolute: false));
        $this->assertDatabaseHas('users', ['username' => 'test_user']);
    }

    public function test_registration_fails_with_invalid_captcha(): void
    {
        Session::put(CaptchaService::SESSION_KEY, 'ABC42');

        $response = $this->post('/register', [
            'username' => 'test_user',
            'password' => 'password',
            'password_confirmation' => 'password',
            'captcha' => 'WRONG',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('captcha');
    }
}
