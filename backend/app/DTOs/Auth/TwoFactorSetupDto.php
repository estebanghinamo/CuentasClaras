<?php

namespace App\DTOs\Auth;

final readonly class TwoFactorSetupDto
{
    public function __construct(
        public string $secret,
        public string $otpauthUrl,
    ) {}

    public function toArray(): array
    {
        return [
            'secret' => $this->secret,
            'otpauth_url' => $this->otpauthUrl,
        ];
    }
}
