<?php

namespace Tests\Feature\Notifications;

use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class PushDeviceTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate(['push_devices', 'personal_access_tokens', 'users']);
        parent::tearDown();
    }

    public function test_register_and_unregister_device(): void
    {
        $user = $this->registerUser(['email' => 'push-user@example.com']);
        $auth = ['Authorization' => "Bearer {$user['token']}"];

        $this->postJson('/api/push-devices', [
            'token' => 'fcm-token-1', 'platform' => 'android', 'device_name' => 'Pixel 8',
        ], $auth)->assertStatus(204);

        $this->assertDatabaseHas('push_devices', ['token' => 'fcm-token-1', 'user_id' => $user['user_id'], 'platform' => 'android']);

        $this->deleteJson('/api/push-devices', ['token' => 'fcm-token-1'], $auth)->assertStatus(204);

        $this->assertDatabaseMissing('push_devices', ['token' => 'fcm-token-1']);
    }

    public function test_rejects_invalid_platform(): void
    {
        $user = $this->registerUser(['email' => 'push-invalid@example.com']);

        $this->postJson('/api/push-devices', [
            'token' => 'fcm-token-2', 'platform' => 'windows_phone',
        ], ['Authorization' => "Bearer {$user['token']}"])->assertStatus(422);
    }

    /** Mismo token registrado por otro usuario (dispositivo compartido / logout+login de otra cuenta) reasigna el dueño. */
    public function test_same_token_reassigns_owner_to_the_newest_user(): void
    {
        $firstUser = $this->registerUser(['email' => 'push-owner-1@example.com']);
        $secondUser = $this->registerUser(['email' => 'push-owner-2@example.com']);

        $this->postJson('/api/push-devices', [
            'token' => 'shared-device-token', 'platform' => 'web',
        ], ['Authorization' => "Bearer {$firstUser['token']}"])->assertStatus(204);

        $this->assertDatabaseHas('push_devices', ['token' => 'shared-device-token', 'user_id' => $firstUser['user_id']]);

        $this->postJson('/api/push-devices', [
            'token' => 'shared-device-token', 'platform' => 'web',
        ], ['Authorization' => "Bearer {$secondUser['token']}"])->assertStatus(204);

        $this->assertDatabaseHas('push_devices', ['token' => 'shared-device-token', 'user_id' => $secondUser['user_id']]);
        $this->assertEquals(1, DB::table('push_devices')->where('token', 'shared-device-token')->count());
    }
}
