<?php

namespace App\DTOs\Notifications;

final readonly class PushDeviceDto
{
    public function __construct(
        public int $userId,
        public string $token,
        public string $platform,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            userId: (int) $row['user_id'],
            token: (string) $row['token'],
            platform: (string) $row['platform'],
        );
    }
}
