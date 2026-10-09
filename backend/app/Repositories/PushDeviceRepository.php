<?php

namespace App\Repositories;

class PushDeviceRepository extends BaseRepository
{
    public function upsert(int $userId, string $token, string $platform, ?string $deviceName): array
    {
        return $this->call('sp_push_device_upsert', [
            'user_id' => $userId,
            'token' => $token,
            'platform' => $platform,
            'device_name' => $deviceName,
        ]);
    }

    public function delete(int $userId, string $token): void
    {
        $this->call('sp_push_device_delete', ['user_id' => $userId, 'token' => $token]);
    }

    /** @param int[] $userIds @return array{user_id:int,token:string,platform:string}[] */
    public function listByUsers(array $userIds): array
    {
        return $this->call('sp_push_device_list_by_users', ['user_ids' => $userIds]);
    }
}
