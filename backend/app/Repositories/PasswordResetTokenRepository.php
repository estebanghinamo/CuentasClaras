<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class PasswordResetTokenRepository extends BaseRepository
{
    public function upsert(string $email, string $tokenHash): void
    {
        $this->call('sp_password_reset_token_upsert', ['email' => $email, 'token_hash' => $tokenHash]);
    }

    public function find(string $email): ?array
    {
        try {
            return $this->call('sp_password_reset_token_get', ['email' => $email]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function delete(string $email): void
    {
        $this->call('sp_password_reset_token_delete', ['email' => $email]);
    }
}
