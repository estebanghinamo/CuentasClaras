<?php

namespace App\Services\Auth;

use App\DTOs\Auth\AuthTokensDto;
use App\DTOs\Auth\TwoFactorSetupDto;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidStateException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenService $tokens,
        private readonly Google2FA $google2fa,
    ) {}

    public function enable(int $userId, string $email): TwoFactorSetupDto
    {
        $row = $this->users->findById($userId);

        if (!empty($row['two_factor_confirmed_at'])) {
            throw new InvalidStateException('El 2FA ya está activo.');
        }

        $secret = $this->google2fa->generateSecretKey(32);

        $this->users->setTwoFactor($userId, Crypt::encryptString($secret), null, false);

        return new TwoFactorSetupDto(
            secret: $secret,
            otpauthUrl: $this->google2fa->getQRCodeUrl('Cuentas Claras', $email, $secret),
        );
    }

    /** @return string[] códigos de recuperación en claro, se devuelven una única vez */
    public function confirm(int $userId, string $code): array
    {
        $row = $this->users->findById($userId);

        if (empty($row['two_factor_secret'])) {
            throw new InvalidStateException('No hay una configuración de 2FA pendiente.');
        }

        $secret = Crypt::decryptString($row['two_factor_secret']);

        if (!$this->google2fa->verifyKey($secret, $code, 1)) {
            throw new InvalidCredentialsException('Código incorrecto.');
        }

        $codes = $this->generateRecoveryCodes();

        $this->users->setTwoFactor(
            $userId,
            Crypt::encryptString($secret),
            Crypt::encryptString(json_encode($codes)),
            true,
        );

        return $codes;
    }

    public function disable(int $userId, string $email, string $password): void
    {
        $row = $this->users->findByEmail($email);

        if ($row === null || !Hash::check($password, $row['password_hash'])) {
            throw new InvalidCredentialsException();
        }

        $this->users->clearTwoFactor($userId);
    }

    public function verifyChallenge(User $challengeUser, string $code): AuthTokensDto
    {
        $userId = (int) $challengeUser->id;
        $row = $this->users->findById($userId);
        $secret = Crypt::decryptString($row['two_factor_secret']);

        $isValid = false;

        if (preg_match('/^[0-9]{6}$/', $code)) {
            $isValid = $this->google2fa->verifyKey($secret, $code, 1);
        } elseif (preg_match('/^[A-Z0-9]{10}$/', $code) && !empty($row['two_factor_recovery_codes'])) {
            $codes = json_decode(Crypt::decryptString($row['two_factor_recovery_codes']), true) ?? [];

            foreach ($codes as $i => $stored) {
                if (hash_equals($stored, $code)) {
                    $isValid = true;
                    unset($codes[$i]);
                    $this->users->useRecoveryCode($userId, Crypt::encryptString(json_encode(array_values($codes))));
                    break;
                }
            }
        }

        if (!$isValid) {
            throw new InvalidCredentialsException('Código incorrecto.');
        }

        $challengeUser->currentAccessToken()?->delete();

        return $this->tokens->issuePair($userId, 'web');
    }

    /** @return string[] */
    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::upper(Str::random(10)))
            ->all();
    }
}
