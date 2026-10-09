<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class UserRepository extends BaseRepository
{
    public function create(string $name, string $email, string $passwordHash, string $locale): array
    {
        return $this->call('sp_user_create', [
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'locale' => $locale,
        ]);
    }

    public function findByEmail(string $email): ?array
    {
        try {
            return $this->call('sp_user_get_by_email', ['email' => $email]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function findById(int $id): ?array
    {
        try {
            return $this->call('sp_user_get_by_id', ['id' => $id]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function updateProfile(int $id, string $name, string $locale, string $theme): array
    {
        return $this->call('sp_user_update_profile', [
            'id' => $id,
            'name' => $name,
            'locale' => $locale,
            'theme' => $theme,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->call('sp_user_update_password', ['id' => $id, 'password_hash' => $passwordHash]);
    }

    public function setTwoFactor(int $id, string $secret, ?string $recoveryCodes, bool $confirmed): void
    {
        $this->call('sp_user_set_two_factor', [
            'id' => $id,
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'confirmed' => $confirmed,
        ]);
    }

    public function clearTwoFactor(int $id): void
    {
        $this->call('sp_user_clear_two_factor', ['id' => $id]);
    }

    public function useRecoveryCode(int $id, string $recoveryCodesEncrypted): void
    {
        $this->call('sp_user_use_recovery_code', [
            'id' => $id,
            'two_factor_recovery_codes' => $recoveryCodesEncrypted,
        ]);
    }
}
