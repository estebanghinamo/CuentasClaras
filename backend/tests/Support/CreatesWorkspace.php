<?php

namespace Tests\Support;

trait CreatesWorkspace
{
    /**
     * Registra un usuario nuevo y crea un workspace del que queda como owner.
     *
     * @return array{token:string,user_id:int,email:string,workspace_id:int}
     */
    protected function createWorkspaceAsOwner(array $workspaceOverrides = [], array $userOverrides = []): array
    {
        $email = $userOverrides['email'] ?? 'owner-'.uniqid().'@example.com';

        $register = $this->postJson('/api/auth/register', array_merge([
            'name' => 'Owner Test',
            'email' => $email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ], $userOverrides));

        $token = $register->json('data.access_token');
        $userId = $register->json('data.user.id');

        $workspace = $this->postJson('/api/workspaces', array_merge([
            'name' => 'Workspace Test',
            'currency' => 'ARS',
            'type' => 'shared_joint',
        ], $workspaceOverrides), ['Authorization' => "Bearer {$token}"]);

        return [
            'token' => $token,
            'user_id' => $userId,
            'email' => $email,
            'workspace_id' => $workspace->json('data.id'),
        ];
    }

    /** Registra un usuario nuevo (sin workspace) y devuelve su token + datos. */
    protected function registerUser(array $overrides = []): array
    {
        $email = $overrides['email'] ?? 'user-'.uniqid().'@example.com';

        $register = $this->postJson('/api/auth/register', array_merge([
            'name' => 'Member Test',
            'email' => $email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ], $overrides));

        return [
            'token' => $register->json('data.access_token'),
            'user_id' => $register->json('data.user.id'),
            'email' => $email,
        ];
    }
}
