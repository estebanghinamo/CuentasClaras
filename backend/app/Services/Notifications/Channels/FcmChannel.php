<?php

namespace App\Services\Notifications\Channels;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Push real vía FCM HTTP v1 (M-17 §3/§8) - sin kreait/laravel-firebase (no
 * instala con PHP 8.2 de este proyecto, ver ERROR_LOG.md), en su lugar
 * google/auth resuelve el token OAuth2 del Service Account y se llama a la
 * API REST directo con el cliente Http de Laravel. Las credenciales viven
 * en FIREBASE_CREDENTIALS_BASE64 (.env, el JSON del Service Account
 * codificado en base64 en una sola línea), nunca como archivo en el repo.
 *
 * Si la variable no está seteada (dev sin Firebase todavía) no se envía
 * nada y se loguea - nunca lanza excepción hacia el dispatcher.
 */
class FcmChannel
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /** @return array{sent:bool, invalid_token:bool} */
    public function send(string $token, string $title, string $body, array $data): array
    {
        $credentials = $this->decodeCredentials();

        if ($credentials === null) {
            Log::info('FcmChannel: FIREBASE_CREDENTIALS_BASE64 no configurada, push no enviado.', ['token' => $token]);

            return ['sent' => false, 'invalid_token' => false];
        }

        try {
            $projectId = $credentials['project_id'] ?? null;
            if (!is_string($projectId)) {
                Log::warning('FcmChannel: las credenciales no tienen project_id.');

                return ['sent' => false, 'invalid_token' => false];
            }

            $accessToken = $this->fetchAccessToken($credentials);

            $stringData = array_map(strval(...), $data);

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        // FCM exige un mapa: un array PHP vacío serializa como '[]' en JSON, no '{}'.
                        'data' => $stringData === [] ? (object) [] : $stringData,
                    ],
                ]);

            if ($response->successful()) {
                return ['sent' => true, 'invalid_token' => false];
            }

            // FCM responde 404 UNREGISTERED / 400 INVALID_ARGUMENT cuando el
            // token ya no es válido (app desinstalada, token rotado) - el
            // dispatcher borra el push_device en ese caso.
            $errorStatus = $response->json('error.status');
            $invalidToken = in_array($errorStatus, ['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT'], true);

            Log::warning('FcmChannel: envío falló.', ['status' => $response->status(), 'body' => $response->body()]);

            return ['sent' => false, 'invalid_token' => $invalidToken];
        } catch (Throwable $e) {
            Log::error('FcmChannel: excepción al enviar push.', ['message' => $e->getMessage()]);

            return ['sent' => false, 'invalid_token' => false];
        }
    }

    /** @return array<string,mixed>|null */
    private function decodeCredentials(): ?array
    {
        $base64 = config('firebase.credentials_base64');

        if (!is_string($base64) || $base64 === '') {
            return null;
        }

        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            Log::warning('FcmChannel: FIREBASE_CREDENTIALS_BASE64 no es base64 válido.');

            return null;
        }

        $credentials = json_decode($decoded, true);
        if (!is_array($credentials)) {
            Log::warning('FcmChannel: FIREBASE_CREDENTIALS_BASE64 no decodifica a un JSON válido.');

            return null;
        }

        return $credentials;
    }

    private function fetchAccessToken(array $credentials): string
    {
        $serviceAccount = new ServiceAccountCredentials(self::SCOPE, $credentials);
        $token = $serviceAccount->fetchAuthToken();

        return (string) $token['access_token'];
    }
}
