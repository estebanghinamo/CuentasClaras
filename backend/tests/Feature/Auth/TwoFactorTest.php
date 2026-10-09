<?php

namespace Tests\Feature\Auth;

use PragmaRX\Google2FA\Google2FA;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate(['personal_access_tokens', 'password_reset_tokens', 'users']);
        parent::tearDown();
    }

    private function registerAndGetAccessToken(string $email): string
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => $email,
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        return $response->json('data.access_token');
    }

    public function test_full_two_factor_lifecycle(): void
    {
        $access = $this->registerAndGetAccessToken('2fa@example.com');
        $headers = ['Authorization' => "Bearer {$access}"];

        // Enable: devuelve secret + otpauth_url
        $enable = $this->postJson('/api/auth/2fa/enable', [], $headers);
        $enable->assertStatus(200)->assertJsonStructure(['data' => ['secret', 'otpauth_url']]);
        $secret = $enable->json('data.secret');

        // Confirm con un código TOTP válido generado con el mismo secret
        $code = (new Google2FA())->getCurrentOtp($secret);
        $confirm = $this->postJson('/api/auth/2fa/confirm', ['code' => $code], $headers);
        $confirm->assertStatus(200)->assertJsonCount(8, 'data.recovery_codes');

        // Login ahora exige el segundo factor
        $login = $this->postJson('/api/auth/login', ['email' => '2fa@example.com', 'password' => 'Password123']);
        $login->assertStatus(401)->assertJsonPath('error.code', 'TWO_FACTOR_REQUIRED');
        $challengeToken = $login->json('error.details.challenge_token');

        // Verify con un código TOTP fresco (el reloj pudo avanzar de ventana de 30s)
        $freshCode = (new Google2FA())->getCurrentOtp($secret);
        $verify = $this->postJson(
            '/api/auth/2fa/verify',
            ['code' => $freshCode],
            ['Authorization' => "Bearer {$challengeToken}"],
        );
        $verify->assertStatus(200)->assertJsonStructure(['data' => ['access_token']]);

        // Disable requiere la contraseña
        $newAccess = $verify->json('data.access_token');
        $disable = $this->deleteJson(
            '/api/auth/2fa',
            ['password' => 'Password123'],
            ['Authorization' => "Bearer {$newAccess}"],
        );
        $disable->assertStatus(204);
    }

    public function test_enable_twice_is_invalid_state(): void
    {
        $access = $this->registerAndGetAccessToken('twice@example.com');
        $headers = ['Authorization' => "Bearer {$access}"];

        $enable = $this->postJson('/api/auth/2fa/enable', [], $headers);
        $secret = $enable->json('data.secret');
        $code = (new Google2FA())->getCurrentOtp($secret);
        $this->postJson('/api/auth/2fa/confirm', ['code' => $code], $headers);

        $response = $this->postJson('/api/auth/2fa/enable', [], $headers);

        $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');
    }
}
