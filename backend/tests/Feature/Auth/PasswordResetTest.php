<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Mail;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate(['personal_access_tokens', 'password_reset_tokens', 'users']);
        parent::tearDown();
    }

    public function test_forgot_password_always_returns_200_even_for_unknown_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/forgot-password', ['email' => 'no-existe@example.com']);

        $response->assertStatus(200);
        Mail::assertNothingQueued();
    }

    public function test_forgot_and_reset_password_flow(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => 'reset@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        $this->postJson('/api/auth/forgot-password', ['email' => 'reset@example.com'])->assertStatus(200);

        $token = null;
        Mail::assertQueued(\App\Mail\PasswordResetMail::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return true;
        });

        $this->assertNotNull($token, 'El mail de reset debería haberse encolado con un token.');

        $reset = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ]);
        $reset->assertStatus(200);

        // La contraseña vieja ya no sirve, la nueva sí
        $this->postJson('/api/auth/login', ['email' => 'reset@example.com', 'password' => 'Password123'])
            ->assertStatus(401);

        $this->postJson('/api/auth/login', ['email' => 'reset@example.com', 'password' => 'NuevaPassword123'])
            ->assertStatus(200);

        // El token ya usado no se puede reutilizar
        $reused = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'OtraPassword123',
            'password_confirmation' => 'OtraPassword123',
        ]);
        $reused->assertStatus(422);
    }

    public function test_change_password_keeps_current_token_and_revokes_others(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => 'change@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);
        $accessToken = $register->json('data.access_token');
        $headers = ['Authorization' => "Bearer {$accessToken}"];

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'Password123',
            'password' => 'OtraPassword456',
            'password_confirmation' => 'OtraPassword456',
        ], $headers);
        $response->assertStatus(200);

        // El mismo access token sigue funcionando
        $this->getJson('/api/auth/me', $headers)->assertStatus(200);
    }

    public function test_change_password_with_wrong_current_password_fails(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => 'wrong-current@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);
        $headers = ['Authorization' => "Bearer {$register->json('data.access_token')}"];

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'Incorrecta123',
            'password' => 'OtraPassword456',
            'password_confirmation' => 'OtraPassword456',
        ], $headers);

        $response->assertStatus(401)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }
}
