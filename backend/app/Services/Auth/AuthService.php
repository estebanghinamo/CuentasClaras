<?php

namespace App\Services\Auth;

use App\DTOs\Auth\AuthTokensDto;
use App\DTOs\Auth\UserDto;
use App\Exceptions\ConflictException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\StoredProcedureException;
use App\Exceptions\TwoFactorRequiredException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Support\SpErrorMapper;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenService $tokens,
    ) {}

    public function register(array $data): AuthTokensDto
    {
        $passwordHash = Hash::make($data['password']);

        try {
            $row = $this->users->create($data['name'], $data['email'], $passwordHash, 'es');
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e, [
                'DUPLICATE' => new ConflictException('Ya existe una cuenta con ese email.'),
            ]);
        }

        return $this->tokens->issuePair((int) $row['id'], $data['device_name'] ?? 'web');
    }

    public function login(string $email, string $password, string $deviceName = 'web'): AuthTokensDto
    {
        $row = $this->users->findByEmail($email);

        if ($row === null) {
            // Costo equivalente a un Hash::check real, para que el tiempo de respuesta
            // no delate si el email existe o no.
            Hash::make($password);
            throw new InvalidCredentialsException();
        }

        if (!Hash::check($password, $row['password_hash'])) {
            RateLimiter::hit("login:{$email}");
            throw new InvalidCredentialsException();
        }

        RateLimiter::clear("login:{$email}");

        if (!empty($row['two_factor_confirmed_at'])) {
            $user = $this->tokens->hydrate((int) $row['id']);
            $ttl = (int) config('cuentas.two_factor_challenge_ttl_minutes');
            $challenge = $user->createToken('2fa-challenge', ['2fa:verify'], now()->addMinutes($ttl));

            throw new TwoFactorRequiredException(
                $challenge->plainTextToken,
                Carbon::instance($challenge->accessToken->expires_at)->utc()->format('Y-m-d\TH:i:s\Z'),
            );
        }

        return $this->tokens->issuePair((int) $row['id'], $deviceName);
    }

    public function refresh(string $refreshToken): AuthTokensDto
    {
        $accessToken = PersonalAccessToken::findToken($refreshToken);

        if (
            $accessToken === null
            || !$accessToken->can('refresh')
            || ($accessToken->expires_at !== null && Carbon::instance($accessToken->expires_at)->isPast())
        ) {
            throw new AuthenticationException('La sesión expiró, iniciá sesión de nuevo.');
        }

        $userId = (int) $accessToken->tokenable_id;

        // Rotación: el refresh usado se borra una sola vez; se limpian los access vencidos.
        $accessToken->delete();

        $user = $this->tokens->hydrate($userId);
        $user->tokens()->where('name', 'access')->where('expires_at', '<', now())->delete();

        return $this->tokens->issuePair($userId, 'web');
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
        $user->tokens()->where('name', 'refresh')->delete();
    }

    public function me(int $userId): UserDto
    {
        return UserDto::fromArray($this->users->findById($userId));
    }

    public function updateProfile(int $userId, string $name, string $locale, string $theme): UserDto
    {
        return UserDto::fromArray($this->users->updateProfile($userId, $name, $locale, $theme));
    }
}
