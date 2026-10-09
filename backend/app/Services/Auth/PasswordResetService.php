<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidCredentialsException;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Repositories\PasswordResetTokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordResetTokenRepository $resetTokens,
    ) {}

    public function forgot(string $email): void
    {
        $row = $this->users->findByEmail($email);

        if ($row === null) {
            // Mismo costo de tiempo aproximado que el camino feliz, sin filtrar si el email existe.
            Hash::make('dummy-password-for-timing');

            return;
        }

        $token = Str::random(64);
        $this->resetTokens->upsert($email, hash('sha256', $token));

        Mail::to($email)->queue(new PasswordResetMail($email, $token));
    }

    public function reset(string $email, string $token, string $password): void
    {
        $row = $this->resetTokens->find($email);
        $ttl = (int) config('cuentas.password_reset_ttl_minutes');

        $valid = $row !== null
            && hash_equals($row['token_hash'], hash('sha256', $token))
            && now()->diffInMinutes($row['created_at']) <= $ttl;

        if (!$valid) {
            throw ValidationException::withMessages(['token' => ['El enlace es inválido o expiró.']]);
        }

        $user = $this->users->findByEmail($email);
        $this->users->updatePassword((int) $user['id'], Hash::make($password));
        $this->resetTokens->delete($email);

        $this->revokeAllTokens((int) $user['id']);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        $row = $this->users->findByEmail($user->email);

        if ($row === null || !Hash::check($currentPassword, $row['password_hash'])) {
            throw new InvalidCredentialsException('La contraseña actual es incorrecta.');
        }

        $this->users->updatePassword((int) $user->id, Hash::make($newPassword));

        $currentTokenId = $user->currentAccessToken()?->id;
        $user->tokens()
            ->when($currentTokenId, fn ($query) => $query->where('id', '!=', $currentTokenId))
            ->delete();
    }

    private function revokeAllTokens(int $userId): void
    {
        $user = new User();
        $user->forceFill(['id' => $userId]);
        $user->exists = true;
        $user->tokens()->delete();
    }
}
