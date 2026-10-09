<?php

namespace Tests\Feature\Auth;

use Tests\Support\ResetsTables;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate(['personal_access_tokens', 'password_reset_tokens', 'users']);
        parent::tearDown();
    }

    private function registerAndGetToken(string $email): string
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Esteban', 'email' => $email,
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        return $register->json('data.access_token');
    }

    public function test_update_profile_persists_theme(): void
    {
        $accessToken = $this->registerAndGetToken('theme@example.com');

        $response = $this->putJson(
            '/api/auth/profile',
            ['name' => 'Esteban', 'locale' => 'es', 'theme' => 'dark'],
            ['Authorization' => "Bearer {$accessToken}"],
        );

        $response->assertStatus(200)->assertJsonPath('data.theme', 'dark');

        $me = $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$accessToken}"]);
        $me->assertJsonPath('data.theme', 'dark');
    }

    public function test_update_profile_rejects_invalid_theme(): void
    {
        $accessToken = $this->registerAndGetToken('badtheme@example.com');

        $response = $this->putJson(
            '/api/auth/profile',
            ['name' => 'Esteban', 'locale' => 'es', 'theme' => 'purple'],
            ['Authorization' => "Bearer {$accessToken}"],
        );

        $response->assertStatus(422);
    }
}
