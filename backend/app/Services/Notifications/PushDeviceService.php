<?php

namespace App\Services\Notifications;

use App\DTOs\Notifications\PushDeviceDto;
use App\Repositories\PushDeviceRepository;

/** Registro de dispositivos para push (M-17) - token único por dispositivo,
 * se reasigna de usuario si cambia el dueño. */
class PushDeviceService
{
    public function __construct(private readonly PushDeviceRepository $devices)
    {
    }

    public function register(int $userId, string $token, string $platform, ?string $deviceName): void
    {
        $this->devices->upsert($userId, $token, $platform, $deviceName);
    }

    public function unregister(int $userId, string $token): void
    {
        $this->devices->delete($userId, $token);
    }

    /** @param int[] $userIds @return PushDeviceDto[] */
    public function listByUsers(array $userIds): array
    {
        return array_map(fn (array $row) => PushDeviceDto::fromArray($row), $this->devices->listByUsers($userIds));
    }
}
