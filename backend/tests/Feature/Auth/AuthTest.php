<?php

namespace Tests\Feature\Auth;

use Tests\Support\ResetsTables;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate(['personal_access_tokens', 'password_reset_tokens', 'users']);
        parent::tearDown();
    }

    public function test_register_returns_token_pair(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Esteban',
            'email' => 'esteban@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'esteban@example.com')
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'user' => ['id', 'name', 'email']]]);
    }

    public function test_register_with_duplicate_email_returns_conflict(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => 'dup@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Otro', 'email' => 'dup@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(409)->assertJsonPath('error.code', 'CONFLICT');
    }

    public function test_login_with_correct_credentials_returns_tokens(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => 'login@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.user.email', 'login@example.com');
    }

    public function test_login_with_wrong_password_returns_invalid_credentials(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => 'wrong@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'Incorrecta123',
        ]);

        $response->assertStatus(401)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_login_with_unknown_email_returns_same_error_as_wrong_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'no-existe@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(401)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_me_with_access_token_returns_user(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => 'me@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);
        $accessToken = $register->json('data.access_token');

        $response = $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$accessToken}"]);

        $response->assertStatus(200)->assertJsonPath('data.email', 'me@example.com');
    }

    public function test_me_with_refresh_token_is_forbidden(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => 'refresh-me@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);
        $refreshToken = $register->json('data.refresh_token');

        $response = $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$refreshToken}"]);

        $response->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_me_without_token_is_unauthenticated(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_refresh_rotates_tokens_and_invalidates_the_old_one(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => 'refresh@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);
        $oldRefresh = $register->json('data.refresh_token');

        $first = $this->postJson('/api/auth/refresh', ['refresh_token' => $oldRefresh]);
        $first->assertStatus(200)->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);

        $second = $this->postJson('/api/auth/refresh', ['refresh_token' => $oldRefresh]);
        $second->assertStatus(401);
    }
}
