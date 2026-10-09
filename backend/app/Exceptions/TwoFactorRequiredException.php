<?php

namespace App\Exceptions;

class TwoFactorRequiredException extends DomainException
{
    public function __construct(string $challengeToken, string $expiresAt)
    {
        parent::__construct(
            'TWO_FACTOR_REQUIRED',
            'Se requiere el segundo factor de autenticación.',
            401,
            [
                'two_factor_required' => true,
                'challenge_token' => $challengeToken,
                'expires_at' => $expiresAt,
            ],
        );
    }
}
