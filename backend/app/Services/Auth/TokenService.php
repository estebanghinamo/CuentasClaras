<?php

namespace App\Services\Auth;

use App\DTOs\Auth\AuthTokensDto;
use App\DTOs\Auth\UserDto;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;

class TokenService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function issuePair(int $userId, string $deviceName = 'web'): AuthTokensDto
    {
        $user = $this->hydrate($userId);

        $accessTtl = (int) config('cuentas.access_token_ttl_minutes');
        $refreshTtl = (int) config('cuentas.refresh_token_ttl_minutes');

        $access = $user->createToken('access', ['access'], now()->addMinutes($accessTtl));
        $refresh = $user->createToken('refresh', ['refresh'], now()->addMinutes($refreshTtl));

        $this->pruneOldRefreshTokens($userId);

        $row = $this->users->findById($userId);

        return new AuthTokensDto(
            accessToken: $access->plainTextToken,
            accessExpiresAt: Carbon::instance($access->accessToken->expires_at)->utc()->format('Y-m-d\TH:i:s\Z'),
            refreshToken: $refresh->plainTextToken,
            refreshExpiresAt: Carbon::instance($refresh->accessToken->expires_at)->utc()->format('Y-m-d\TH:i:s\Z'),
            user: UserDto::fromArray($row),
        );
    }

    public function hydrate(int $userId): User
    {
        $user = new User();
        $user->forceFill(['id' => $userId]);
        $user->exists = true;

        return $user;
    }

    private function pruneOldRefreshTokens(int $userId): void
    {
        $max = (int) config('cuentas.max_refresh_tokens_per_user');
        $user = $this->hydrate($userId);

        $ids = $user->tokens()->where('name', 'refresh')->orderByDesc('created_at')->pluck('id');

        if ($ids->count() > $max) {
            $user->tokens()->whereIn('id', $ids->slice($max))->delete();
        }
    }
}
